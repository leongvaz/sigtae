# Implementación búsqueda de RPE

Este documento describe el patrón usado en SIGTAE para **buscar un RPE** y **autorrellenar** datos (nombre, correo, cargo) en una pantalla. El patrón es: **Frontend → API interna de tu app (proxy) → Servicio externo**.

> Idea clave: el navegador **no** consulta directamente el servicio externo. En su lugar, consulta un endpoint de **tu propio backend**, que actúa como proxy y aplica control de sesión/permisos.

---

## 1) Flujo general

- **Usuario** captura un RPE y presiona **Buscar RPE**.
- **Frontend (JS)** llama a un endpoint de tu app:
  - `GET {BASE}/api/usuarios.php?action=lookup_rpe&rpe=G46BG`
- **Backend (API interna)**:
  - valida sesión
  - valida permisos
  - valida el formato del RPE
  - consulta el servicio externo (por ejemplo, un API interno en red privada)
  - normaliza la respuesta
  - devuelve JSON al frontend
- **Frontend**:
  - si `ok === true`, rellena los inputs (Nombre/Correo/etc.)
  - si `ok === false`, muestra error

---

## 2) Endpoint interno (proxy) en SIGTAE

En SIGTAE se implementa en:

- `public/api/usuarios.php`

### Acceso / Seguridad

- **Requiere sesión** (usa `requireAuth()`).
- **Restringe permisos** (usa `UserAdminGuard::canAccessUserAdmin($user)`).
  - Si no cumple, responde **403** con `{ ok:false }`.

### Validación del RPE

- Convierte el RPE a mayúsculas y recorta espacios.
- Rechaza si está vacío o si no cumple regex:
  - `^[A-Z0-9]{1,8}$`

### Llamada al servicio externo

El endpoint construye una URL y hace un request server-side, por ejemplo:

- `http://10.4.157.20/api/consulta/{RPE}`

Y aplica:

- timeout corto (ej. 4 segundos)
- header `Accept: application/json`

### Normalización de respuesta

Devuelve siempre un JSON “limpio” para el frontend:

- `ok: true/false`
- `message` en caso de error
- `item` con campos útiles:
  - `rpe`
  - `nombre`
  - `email`
  - `cargo`
  - `habilitado`
  - `bloqueado`

Ejemplo (éxito):

```json
{
  "ok": true,
  "item": {
    "rpe": "G46BG",
    "nombre": "LEON GONZALEZ VAZQUEZ",
    "email": "leon.gonzalezv@cfe.mx",
    "cargo": "SUPERVISOR",
    "habilitado": "1",
    "bloqueado": "0"
  }
}
```

Ejemplo (error):

```json
{
  "ok": false,
  "message": "RPE inválido."
}
```

---

## 3) Frontend: ejemplo de implementación

### HTML mínimo (inputs + botón)

```html
<div class="row g-2 align-items-end">
  <div class="col-md-3">
    <label class="form-label small fw-semibold mb-1">RPE *</label>
    <input id="rpe" class="form-control form-control-sm" placeholder="G46BG">
  </div>
  <div class="col-md-2">
    <button id="btnLookup" class="btn btn-sm btn-outline-primary" type="button">Buscar RPE</button>
  </div>
  <div class="col-md-4">
    <label class="form-label small fw-semibold mb-1">Nombre *</label>
    <input id="nombre" class="form-control form-control-sm">
  </div>
  <div class="col-md-3">
    <label class="form-label small fw-semibold mb-1">Correo</label>
    <input id="email" class="form-control form-control-sm">
  </div>
</div>
<div id="rpeMsg" class="form-text"></div>
```

### JS (fetch + autollenado + manejo de error)

```js
(function () {
  const base = window.SIGTAE_BASE_PATH || ""; // o tu basePath
  const btn = document.getElementById("btnLookup");
  const msg = document.getElementById("rpeMsg");

  function setMsg(text, isError) {
    msg.textContent = text || "";
    msg.style.color = isError ? "#b91c1c" : "#0f766e";
  }

  async function lookupRpe(rpe) {
    const url = base + "/api/usuarios.php?action=lookup_rpe&rpe=" + encodeURIComponent(rpe);
    const res = await fetch(url, { headers: { "X-Requested-With": "fetch" } });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || data.ok === false) {
      throw new Error((data && data.message) ? data.message : ("HTTP " + res.status));
    }
    return data.item;
  }

  btn.addEventListener("click", async () => {
    const rpe = String(document.getElementById("rpe").value || "").trim().toUpperCase();
    if (!rpe) return setMsg("Capture un RPE.", true);

    setMsg("Consultando…", false);
    try {
      const item = await lookupRpe(rpe);
      document.getElementById("nombre").value = item.nombre || "";
      document.getElementById("email").value = item.email || "";
      setMsg("Datos cargados desde el servicio.", false);
    } catch (e) {
      setMsg(e.message || String(e), true);
    }
  });
})();
```

---

## 4) Recomendaciones para replicarlo en otro sistema

- **Siempre usa proxy server-side** para evitar problemas de CORS/red interna.
- **Aplica permisos** en el endpoint interno (no en el frontend).
- **Valida el RPE** antes de consultar (reduce carga y errores).
- **Normaliza** el JSON de salida para no depender del formato del proveedor.
- **Timeout corto** y mensajes claros al usuario.

---

## 5) Checklist rápido

- [ ] Endpoint interno `/api/...` funcionando con sesión
- [ ] Permisos definidos (quién puede usar el lookup)
- [ ] Validación regex RPE
- [ ] Request server-side al servicio externo
- [ ] Respuesta JSON `{ ok, item|message }`
- [ ] Frontend: botón + fetch + autollenado + mensajes

