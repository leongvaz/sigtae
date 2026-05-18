# ALBA — Administrador de Libros y Bitácoras de Auditorías

**ALBA** es un sistema web para la carga, consulta y gestión de evidencias documentales asociadas a requisitos de laboratorio (capítulos 4 a 8) y a bitácoras de auditoría interna y externa. Permite estructurar la información por secciones, apartados y subíndices, subir documentos por cada punto, llevar un avance general y registrar no conformidades vinculadas a las auditorías.

---

## Nombre y propósito

| Sigla | Significado |
|-------|-------------|
| **A** | Administrador |
| **L** | Libros |
| **B** | Bitácoras |
| **A** | Auditorías |

**ALBA = Administrador de Libros y Bitácoras de Auditorías.**

El sistema sirve para:

- Gestionar **evidencias** por requisitos (secciones 4 a 8) con jerarquía de apartados y subíndices (a, b, c…).
- Mostrar un **avance general** (sumatoria de secciones 4–8).
- Gestionar **Auditoría Interna** y **Auditoría Externa**: hasta 2 documentos por tipo y listado de **no conformidades** (ID, comentario, PDF opcional) con alta, edición y eliminación.

---

## Estado actual y avance (contexto del proyecto)

- **Fase:** MVP funcional en producción local (Laragon). Sin base de datos; toda la persistencia en JSON y archivos en `uploads/`.
- **Acceso:** Solo usuarios que (1) validan en Directorio Activo y (2) están en la whitelist en `data/data.json` con `estado: 1` y `proceso: "distribucion"`. Ver sección *Configuración de usuarios*.
- **Implementado:**
  - Login con API AD + whitelist; sesión PHP; inactividad 30 min.
  - Dashboard con 8 cards de requisitos (4–8), Avance General (9), Auditoría Interna (10), Auditoría Externa (11).
  - Árbol completo de requisitos en `structure.json` (secciones 4–8 con subíndices a), b), c)… separados como nodos hijos).
  - Carga de evidencias por nodo hoja; comentario opcional; vista previa (PDF/imagen); eliminación.
  - Avance por sección y avance general (sumatoria 4–8).
  - Auditoría Interna/Externa: hasta 2 documentos por tipo; no conformidades (ID, comentario, PDF) con CRUD.
  - Caso especial 8.1.2: panel de referencias con enlaces a 8.2–8.9.
- **No implementado:** Base de datos, auditoría de acciones, logs, roles distintos por usuario (el campo `admin` existe en data.json pero no se usa aún en la lógica).

---

## Stack técnico

| Componente | Tecnología |
|------------|------------|
| Backend | PHP 8+ (Laragon / Apache) |
| Frontend | HTML5 + Bootstrap 5 + JavaScript (vanilla) |
| Persistencia | JSON en disco |
| Autenticación | Directorio Activo (API externa) + whitelist local (`data/data.json`) |
| Uploads | Carpeta `/uploads/` dentro del proyecto |

---

## Instalación (Laragon)

1. Copiar la carpeta **php** (o renombrarla a **alba**) dentro de la carpeta **www** de Laragon (ej. `C:\laragon\www`).
2. Estructura esperada:
   - `Login/`
   - `api/`
   - `data/`
   - `uploads/`
   - `assets/`
   - `index.php`
3. Asegurar que **data** y **uploads** existan y tengan permisos de escritura. En Linux: `chmod 755 data uploads`.
4. Iniciar Laragon (Apache + PHP) y abrir en el navegador:
   - `http://alba.test` (si la carpeta se llama `alba`) o
   - `http://php.test` / `http://localhost/php/` según configuración.

---

## Configuración de usuarios (whitelist)

El archivo **data/data.json** define qué usuarios pueden acceder tras validar contra el Directorio Activo.

Ejemplo:

```json
{
  "users": [
    {
      "user": "RPE_MAYUSCULAS",
      "nombre": "NOMBRE COMPLETO",
      "zona": "Chapingo",
      "admin": "0",
      "estado": 1,
      "proceso": "distribucion"
    }
  ]
}
```

- **user:** RPE en mayúsculas (debe coincidir con el usuario en AD).
- **estado:** `1` para permitir acceso.
- **proceso:** debe ser `"distribucion"`.

Si el usuario no está en la lista o no cumple condiciones: *"El usuario no tiene permitido el acceso"*. Si falla la contraseña en AD: *"Usuario o Password incorrecto"*.

---

## Autenticación (Directorio Activo)

La validación de usuario y contraseña se hace contra una API externa de Directorio Activo.

- **URL de la API:** definida en `Login/login.class.php` (constante `AD_URL`), por ejemplo:  
  `http://api.dvmc.cfemex.com/ad/validacion`
- **Método:** POST, `application/x-www-form-urlencoded`.
- **Parámetros:** `rpe`, `psw`.
- **Respuesta esperada:** JSON con `"success": true` cuando las credenciales son válidas.

Tras validación exitosa en AD, se comprueba la whitelist en `data/data.json`.

---

## Estructura de datos

### data/structure.json

- **sections:** secciones (cards) con `id`, `order`, `title`, `isActive`.
- **nodeItems:** árbol de apartados y subíndices. Cada nodo tiene:
  - `id` (ej. `4.1`, `5.5.1`, `6.6.2.3`)
  - `sectionId`, `parentId` (null para raíz de sección)
  - `title`, `order`, `isLeaf` (true = nodo hoja donde se suben documentos)

Los puntos con subíndices a), b), c) están modelados como padre (no hoja) con hijos (hojas) numerados (ej. 5.5 → 5.5.1, 5.5.2, 5.5.3).

### data/documents.json

- Lista de documentos subidos: `id`, `nodeItemId`, `originalName`, `storedName`, `mimeType`, `size`, `createdAt`, y opcionalmente `comment`.

### data/noconformidades.json

- Generado por la API de no conformidades.
- Estructura: `auditoria_interna` y `auditoria_externa`, cada uno con un array de objetos `{ id, refId, comment, documentId, originalName, createdAt }`.

### uploads/

- Archivos físicos con nombre seguro (hex + extensión).

---

## Funcionalidad por pantalla

### Dashboard (index.php)

- **Cards 4–8:** Requisitos Generales, Requisitos relativos a la estructura, a recursos, al proceso, al sistema de gestión. Cada una muestra número de sección, barra de progreso y “X/Y atendidos”. Al hacer clic se abre el modal de subíndices.
- **Card 9 — Avance General:** Sin número. Al hacer clic se abre un modal con la sumatoria del avance de las secciones 4 a 8 (tabla por sección y total).
- **Card 10 — Auditoría Interna:** Modal con (1) hasta 2 documentos de auditoría (subir/listar/eliminar) y (2) listado de no conformidades (ID, comentario, PDF opcional; agregar, editar, eliminar).
- **Card 11 — Auditoría Externa:** Mismo esquema que Auditoría Interna, con datos independientes.

### Navegación por requisitos (cards 4–8)

1. Clic en la card → se abre el modal con los apartados de primer nivel (ej. 4.1 Imparcialidad, 4.2 Confidencialidad).
2. Clic en un apartado **no hoja** → se muestran sus subíndices (ej. 4.1.1, 4.1.2, …) y el botón “Atrás”.
3. Clic en un apartado **hoja** → se cierra el modal de nodos y se abre el modal de documentos para subir/listar/vista previa/eliminar evidencias. Opcionalmente se puede agregar comentario al subir.
4. En la sección 4, el subíndice 8.1.2 (Opción A) muestra un panel con enlaces a 8.2–8.9; al hacer clic en uno se navega a ese apartado en el modal de nodos.

### Cerrar sesión e inactividad

- “Cerrar sesión” en cabecera → `Login/cerrarSession.php` → redirección al login.
- Tras 30 minutos de inactividad, la siguiente petición puede devolver “Sesión expirada” y redirigir al login.

---

## Estructura de archivos del proyecto

```
php/
├── Login/
│   ├── login.php           (formulario y lógica de login)
│   ├── login.class.php     (validación AD + whitelist)
│   ├── comprobarSession.php
│   ├── inactividad.php
│   └── cerrarSession.php
├── api/
│   ├── bootstrap.php       (sesión + inactividad)
│   ├── sections.php        (GET: secciones con progreso; sección 9 = avance general)
│   ├── nodes.php           (GET: hijos de un nodo; incl. attendedLeaves/totalLeaves en padres)
│   ├── documents.php       (GET/POST: listar/subir por nodeId; auditoria_interna/externa máx. 2)
│   ├── document_delete.php (POST: eliminar por documentId)
│   ├── document_inline.php  (GET: servir archivo para vista previa)
│   └── noconformidades.php  (GET/POST/PUT/DELETE: no conformidades por tipo interna/externa)
├── data/
│   ├── data.json           (whitelist de usuarios)
│   ├── structure.json      (secciones + nodeItems)
│   ├── documents.json      (registro de documentos subidos)
│   └── noconformidades.json (generado por API)
├── uploads/                (archivos subidos)
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── index.php               (dashboard)
└── README.md
```

---

## API (protegida por sesión)

| Método | Endpoint | Uso |
|--------|----------|-----|
| GET | api/sections.php | Secciones con progressPercent, attendedLeaves, totalLeaves, isActive. Sección 9 = sumatoria 4–8. |
| GET | api/nodes.php | Parámetros: sectionId, parentId (opcional). Devuelve hijos; en padres incluye attendedLeaves/totalLeaves. |
| GET | api/documents.php | Parámetro: nodeId. Lista documentos del nodo (incl. auditoria_interna, auditoria_externa). |
| POST | api/documents.php | multipart: nodeId, files[] (y opcional comment). Sube archivos. Máx. 2 si nodeId es auditoria_interna o auditoria_externa. |
| POST | api/document_delete.php | JSON: documentId. Elimina documento y archivo. |
| GET | api/document_inline.php | Parámetro: id. Sirve el archivo para vista previa (inline). |
| GET | api/noconformidades.php | Parámetro: tipo=interna\|externa. Lista no conformidades. |
| POST | api/noconformidades.php | multipart: tipo, refId, comment, file (opcional). Alta de no conformidad. |
| PUT | api/noconformidades.php | JSON: id, tipo, refId, comment. Editar no conformidad. |
| DELETE | api/noconformidades.php | JSON: id, tipo. Elimina no conformidad y documento asociado si existe. |

---

## Seguridad

- Endpoints bajo `/api/` cargan `bootstrap.php` (comprobación de sesión e inactividad).
- Nombres de archivo saneados; extensión y MIME validados; tamaño máximo 25 MB por archivo.
- Rutas de guardado validadas con `realpath` para evitar path traversal.
- Archivos guardados con nombre aleatorio (hex + extensión); no se ejecutan.

---

## Limitaciones y notas

- Diseñado para ejecutarse en entorno HTTP (ej. Laragon local).
- Sin base de datos; persistencia en JSON y archivos en disco.
- Sin auditoría de acciones ni logs de actividad en esta versión.

---

## Resumen del flujo de uso

1. **Login** con RPE y contraseña de Directorio Activo (usuario debe estar en whitelist).
2. **Dashboard:** elegir una card (4–8 para requisitos, 9 para avance general, 10–11 para auditorías).
3. **Requisitos (4–8):** navegar por apartados y subíndices; en hojas, subir evidencias (y opcionalmente comentario).
4. **Avance General (9):** solo consulta del avance agregado.
5. **Auditoría Interna/Externa (10–11):** subir hasta 2 documentos de auditoría y gestionar no conformidades (ID, comentario, PDF).
6. **Cerrar sesión** cuando corresponda.

Este documento describe **ALBA — Administrador de Libros y Bitácoras de Auditorías** y su uso actual.
