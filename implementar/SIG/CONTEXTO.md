# Contexto del proyecto ALBA — Para abrir en otra computadora o nueva terminal

Este documento resume el proyecto para retomarlo en otra máquina o en una nueva sesión de Cursor. Léelo primero si abres el repositorio en otra computadora.

---

## 1. Qué es ALBA

**ALBA = Administrador de Libros y Bitácoras de Auditorías.**

Sistema web PHP (sin framework) para:

- Gestionar **evidencias documentales** por requisitos de laboratorio (capítulos 4–8), con jerarquía de apartados y subíndices (a, b, c…).
- Mostrar **avance general** (sumatoria de secciones 4–8).
- Gestionar **Auditoría Interna** y **Auditoría Externa**: hasta 2 documentos por tipo + listado de **no conformidades** (ID, comentario, PDF opcional) con alta, edición y eliminación.

**Acceso:** Solo quienes validan en Directorio Activo (API externa) y están en la whitelist en `data/data.json` con `estado: 1` y `proceso: "distribucion"`.

---

## 2. Dónde está el proyecto

- **Carpeta de trabajo:** la que contiene `index.php`, `Login/`, `api/`, `data/`, `assets/`, `uploads/`. En la ruta original suele ser algo como `.../Alba/php/` o `.../www/alba/` dentro de Laragon.
- **Para Cursor:** abre como workspace la carpeta que contiene `index.php` (la raíz de la aplicación PHP).

---

## 3. Cómo ejecutarlo

- **Servidor:** PHP con Apache (pensado para **Laragon** en Windows).
- **Pasos:**
  1. Copiar la carpeta del proyecto dentro de `www` de Laragon (ej. `C:\laragon\www\alba`).
  2. Asegurar que existan `data/` y `uploads/` con permisos de escritura.
  3. Iniciar Laragon (Apache + PHP).
  4. Abrir en el navegador: `http://alba.test` (o la URL que tengas configurada para esa carpeta).
- **Login:** RPE y contraseña de Directorio Activo; el usuario debe estar en `data/data.json` con `estado: 1` y `proceso: "distribucion"`.

---

## 4. Estructura de archivos (resumen)

```
php/  (o alba/)
├── index.php              → Dashboard (cards 4–11)
├── Login/
│   ├── login.php          → Pantalla y lógica de login
│   ├── login.class.php    → Validación AD + whitelist (aquí va AD_URL)
│   ├── comprobarSession.php
│   ├── inactividad.php    → 30 min
│   └── cerrarSession.php
├── api/
│   ├── bootstrap.php      → Comprueba sesión en todos los endpoints
│   ├── sections.php       → GET secciones + progreso (9 = avance general)
│   ├── nodes.php          → GET hijos de un nodo (árbol de requisitos)
│   ├── documents.php      → GET/POST documentos por nodeId (y auditoría 2 docs máx.)
│   ├── document_delete.php
│   ├── document_inline.php → Vista previa de archivo
│   └── noconformidades.php → CRUD no conformidades (auditoría interna/externa)
├── data/
│   ├── data.json          → Whitelist de usuarios (quién tiene acceso)
│   ├── structure.json    → Secciones + árbol de nodeItems (requisitos 4–8)
│   ├── documents.json    → Registro de documentos subidos
│   └── noconformidades.json → No conformidades (generado por API)
├── uploads/               → Archivos subidos (nombres hex)
├── assets/
│   ├── css/style.css
│   └── js/app.js          → Lógica del dashboard, modales, llamadas API
├── README.md              → Documentación completa
└── CONTEXTO.md            → Este archivo
```

---

## 5. Dónde se configura qué

| Qué | Dónde |
|-----|--------|
| Usuarios con acceso | `data/data.json` → array `users`; obligatorio `estado: 1`, `proceso: "distribucion"` |
| URL API Directorio Activo | `Login/login.class.php` → constante `AD_URL` |
| Textos y árbol de requisitos (secciones 4–8) | `data/structure.json` → `sections` y `nodeItems` |
| Tiempo de inactividad | `Login/inactividad.php` (30 min) |

---

## 6. Persistencia (sin base de datos)

- **data/structure.json:** secciones (cards) y árbol de apartados/subíndices (`nodeItems`). Los ítems con subíndices a), b), c) son nodos padre con hijos (ej. 5.5 → 5.5.1, 5.5.2, 5.5.3).
- **data/documents.json:** lista de documentos subidos (`nodeItemId` enlaza con un nodo hoja o con `auditoria_interna` / `auditoria_externa`).
- **data/noconformidades.json:** generado por la API; claves `auditoria_interna` y `auditoria_externa` con arrays de no conformidades.
- **uploads/:** archivos físicos con nombre en hex + extensión.

---

## 7. Flujo de la aplicación

1. Usuario entra a la app → si no hay sesión, va a `Login/login.php`.
2. Login: POST con RPE y contraseña → `login.class.php` valida contra AD y contra whitelist → si OK, sesión y redirección a `index.php`.
3. Dashboard: 8 cards de requisitos (4–8), Avance General (9), Auditoría Interna (10), Auditoría Externa (11).
4. Cards 4–8: clic abre modal con árbol de nodos; clic en nodo hoja abre modal de documentos (subir/listar/vista previa/eliminar).
5. Card 9: modal con tabla de avance por sección y total (sumatoria 4–8).
6. Cards 10 y 11: modal con (1) hasta 2 documentos de auditoría y (2) listado de no conformidades (agregar/editar/eliminar).
7. Cerrar sesión → `Login/cerrarSession.php` → vuelve al login.

---

## 8. Estado actual (avance del desarrollo)

- **Completado:** Login AD + whitelist, sesión, inactividad, dashboard, árbol completo de requisitos (4–8 con subíndices a/b/c separados), carga de evidencias por nodo, comentario opcional, vista previa, eliminación, avance por sección y avance general, Auditoría Interna/Externa (2 docs + no conformidades CRUD), caso especial 8.1.2 (referencias a 8.2–8.9).
- **No implementado:** Base de datos, auditoría de acciones, logs, uso del campo `admin` para permisos.

---

## 9. Documentación completa

- **README.md** en la misma carpeta: instalación, configuración de usuarios, API, estructura de datos, seguridad y flujo detallado. Consultar ahí para detalles técnicos y de despliegue.

---

## 10. Cambios rápidos habituales

- **Añadir/quitar usuarios:** editar `data/data.json` (mantener `estado: 1` y `proceso: "distribucion"`).
- **Cambiar URL de AD:** `Login/login.class.php` → `AD_URL`.
- **Cambiar textos o estructura de requisitos:** `data/structure.json` (secciones y nodeItems). Los nodos hoja (`isLeaf: true`) son los que permiten subir documentos.
- **Estilos o textos del dashboard:** `index.php`, `assets/css/style.css`, `assets/js/app.js`.

Con este contexto puedes abrir el proyecto en otra computadora y seguir trabajando. Para más detalle, usar **README.md**.
