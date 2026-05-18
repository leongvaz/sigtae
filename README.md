# SIGTAE — Sistema de Gestión de Tareas (Laboratorio de Medición)

**SIGTAE** es un sistema interno para la **asignación, seguimiento, presentación de evidencias y evaluación de tareas** dentro de una estructura jerárquica del **Laboratorio de Medición**, organizado por **oficinas** (por ejemplo: **Metrología**, **Preparación de Medidores** y **AMI**).

El objetivo es ordenar la operación diaria de un departamento con varias oficinas: que cada jefe pueda asignar tareas a sus colaboradores, dar seguimiento al cumplimiento, recibir evidencias (archivos), evaluar el resultado y medir desempeño con indicadores claros.

---

## 1. Stack tecnológico

### 1.1 Lenguajes y tecnologías (qué, por qué y para qué)

| Capa | Tecnología | ¿Por qué? | ¿Para qué se usa aquí? |
| --- | --- | --- | --- |
| Backend | **PHP 8.0+** (sin framework) | Despliegue simple en Apache/PHP, curva baja, control total. | Páginas en `public/`, lógica en `app/Services`, persistencia por repositorios. |
| Estructura | **PSR-4 opcional** (Composer) + *autoload* manual | No depender de `composer install` en ambientes cerrados, pero mantener orden de namespaces. | `App\*` se carga desde `app/` (con `vendor/` si existe; si no, autoload manual en `app/bootstrap.php`). |
| Frontend | **HTML5 + CSS + JavaScript** | UI rápida y compatible, sin build tooling. | Vistas en `views/` con componentes/herramientas de UI compartidas. |
| UI framework | **Bootstrap 5.3** (CDN) | Consistencia visual y componentes listos. | Layout, formularios, modales, grid y utilidades. |
| Tablas | **DataTables 1.13** (jQuery) | Orden/filtros/paginación sin backend complejo. | Listados de tareas/bitácoras y vistas con filtros. |
| Gráficas | **Chart.js 4.4** | KPIs visuales sin infraestructura adicional. | Dashboard administrativo (por estado, prioridad, etc.). |
| Iconografía | **Bootstrap Icons 1.11** | Librería coherente con Bootstrap. | Navegación, botones y estados. |
| Persistencia | **JSON** (`storage/json/`) | Arranque rápido sin BD; fácil de versionar/respaldar. | `users.json`, `tasks.json`, `history.json`, `delegations.json`, etc. con acceso atómico vía `JsonStorage`. |
| Auth | **Directorio Activo (API)** + *fallback* local | Integración corporativa; continuidad en desarrollo/contingencia. | Login vía API AD; si falla y está habilitado, valida `password_hash` local. |
| Navegación | **PJAX ligero (fetch + DOMParser)** | Mejor UX sin SPA ni framework. | El sidebar/topbar navega sin recargar toda la página (excepto módulos embebidos como SIG). |
| Servidor | Apache (`.htaccess`) o `php -S` | Flexibilidad (prod/dev). | Rewrite a `public/` y ejecución local rápida. |

Dependencias externas: todas vía CDN (Bootstrap, DataTables, Chart.js, jQuery, Bootstrap Icons). No requiere `composer install` para operar: el `bootstrap.php` carga las clases `App\*` con un autoload manual.

---

## 2. Finalidad del sistema

SIGTAE cubre el ciclo completo de una **tarea de trabajo** en el laboratorio:

1. **Asignación**: un jefe (departamento, oficina) o un supervisor habilitado asigna una tarea a un colaborador bajo su alcance, indicando prioridad, fecha límite y modalidad (diaria o programada).
2. **Seguimiento**: el sistema calcula automáticamente el estado (asignada, en proceso, incumplimiento, vencida, atendida) según la fecha límite y las evidencias registradas.
3. **Presentación de evidencias**: el responsable sube uno o varios archivos (fotos, videos, PDF, Word, Excel, etc.) como comprobante del trabajo realizado. Se versionan.
4. **Evaluación**: el asignador (o su equivalente jerárquico) califica como **satisfactoria** o **insatisfactoria**. El sistema aplica reglas de "en tiempo / fuera de tiempo" y reintentos.
5. **Desempeño e indicadores**: todo lo anterior alimenta KPIs, ranking, historial y reportes.

---

## 3. Usuarios, roles y permisos

### 3.1 Niveles jerárquicos

Definidos en `app/config/constants.php`:

| Nivel | Rol | Ejemplos de cargo |
| --- | --- | --- |
| 1 | Jefe de departamento | Jefe Dept. Laboratorio de Medición |
| 2 | Jefe de oficina | Jefe Metrología, Jefe Preparación de Medidores |
| 3 | Supervisor / Colaborador | Supervisores de área |

Adicionalmente un usuario puede tener:

- `es_super_admin = true` → administrador global (ve todo, puede hacer todo).
- `puede_asignar = true` → flag independiente para permitir asignar tareas (útil para supervisores a los que se les delega la capacidad).
- `alcance_asignacion` → `"oficina"` | `"departamento"` | `"todos"`, acota a quién puede asignar tareas.
- `oficina_id`, `departamento_id` → adscripción.

### 3.2 Reglas de permisos (resumen)

- **Asignar** (`PermissionService::canAssignTo`): el actor debe tener `puede_asignar=true` (o ser super admin) y el candidato debe caer dentro de su `alcance_asignacion`. Nunca se asigna tareas a uno mismo (excepto super admin).
- **Evaluar** (`canEvaluate`): el asignador original, el jefe de oficina del responsable, el jefe de departamento del responsable o un super admin.
- **Cancelar** (`canCancelTask`): super admin; o usuarios con `puede_asignar=true` que sean el asignador original o jefe de oficina de la misma oficina que la tarea. Los colaboradores **no** pueden cancelar tareas.
- **Reasignar** (`canReassignTask`): debe poder asignar al nuevo responsable y ser (asignador original | jefe oficina | super admin).
- **Administrar usuarios/oficinas**: únicamente super admin.

---

## 4. Autenticación

Flujo en `AuthService` + `AdDirectoryValidationService`:

1. El usuario ingresa RPE y contraseña en `login.php`.
2. Si `ad_validation.enabled = true` (en `app/config/app.php`), se llama a la API corporativa (`api.dvmc.cfemex.com/ad/validacion`).
3. Si `auth_local_password_fallback = true`, ante fallo de AD (sin respuesta **o** credenciales rechazadas por el directorio) se intenta validar contra el `password_hash` en `storage/json/users.json` (útil para desarrollo y usuarios de prueba que no existen en AD).
4. La sesión se guarda con nombre `sigtae_session` y control de inactividad.

---

## 5. Modelo de datos (JSON)

Archivos en `storage/json/`:

| Archivo | Contenido |
| --- | --- |
| `users.json` | Usuarios: RPE, nombre, cargo, nivel_jerarquico, oficina_id, departamento_id, puede_asignar, alcance_asignacion, es_super_admin, activo. |
| `offices.json` | Oficinas del departamento. |
| `departments.json` | Departamentos (piloto: Laboratorio de Medición). |
| `tasks.json` | Tareas con folio (`TAS-AAAA-NNNN`), responsable, asignador, modalidad, fechas, estado calculado, prioridad, evidencias[], dictamen, evaluación, historial de cancelación/reasignación, etc. |
| `history.json` | Eventos (tarea_creada, evidencia_subida, evaluacion_registrada, tarea_cancelada, tarea_reasignada, delegacion_aplicada, etc.). |
| `delegations.json` | Delegaciones temporales (ausencias). |
| `catalogs.json` | Catálogos auxiliares. |
| `tasks_seed.json` | Semilla cargable desde `/seed-tasks.php`. |

### 5.1 Estados de tarea (calculados)

Calculados en `TaskStateService::computeState` — nunca se guardan "a mano":

- `asignada` — recién creada, aún no se trabaja.
- `en_proceso` — dentro del plazo, sin evidencia.
- `incumplimiento` — venció el plazo y **no** hay evidencia.
- `vencida` — existe evidencia pero se subió **después** de la fecha límite (aún por evaluar).
- `atendida` — la tarea fue presentada y cerrada.
- `cancelada` — baja lógica con motivo obligatorio.

### 5.2 Dictámenes de evaluación

- `satisfactoria` — presentada en tiempo → 100% de desempeño.
- `satisfactoria_fuera_tiempo` — presentada tarde o tras una insatisfactoria → 50%.
- `insatisfactoria` — requiere reintentar con nueva evidencia → 0% hasta que se cierre satisfactoriamente (y ya contará como fuera de tiempo).
- `no_presentada` — vencida sin evidencia → 0%.

---

## 6. Evidencias (archivos)

- Se suben desde el detalle de la tarea, sección **“Presentar evidencia”**.
- Se permite **subir varios archivos a la vez** (`input multiple`).
- Formatos aceptados: imágenes (`jpg/jpeg/png/gif/webp`), video (`mp4/webm/mov`), documentos (`pdf/doc/docx/xls/xlsx/ppt/pptx/csv/txt`), comprimidos (`zip/rar`).
- Se guardan en `storage/uploads/tareas/<id_tarea>/` con un nombre seguro y versionado.
- Se descargan/visualizan vía `public/evidencia.php?task_id=...&ev_id=...`, que valida permisos antes de entregar el archivo.
- **Sin límite de tamaño por aplicación** (configurable con `evidence_upload_max_bytes` en `config/app.php` si se quisiera reactivar). Los límites prácticos quedan sujetos a `php.ini` (`upload_max_filesize`, `post_max_size`, `max_file_uploads`). El `.htaccess` del proyecto intenta subirlos a 2 GB por archivo / 100 archivos, si el servidor permite `php_value`.

> ℹ️ **Fecha de presentación**: se puede ver en el detalle de la tarea (`public/tarea.php`) bajo el campo **“Fecha de presentación”**, que corresponde a `fecha_entrega` (establecida al evaluar) o, en su defecto, a la fecha de la **primera evidencia subida** (`evidencias[0].fecha_subida`).

---

## 7. Pantallas principales

Todas las páginas viven en `public/` y usan el *layout* común `views/layout.php` (sidebar izquierdo, topbar con breadcrumbs, contenedor fluido).

Notas de UI:

- **Navegación tipo PJAX**: los clics en sidebar/topbar cargan contenido por `fetch` y actualizan el contenedor `.sigtae-content` sin recargar toda la página. Algunas páginas se fuerzan a carga completa (por ejemplo, `metrologia-sig.php`) porque inyectan assets específicos.
- **Estilo visual**: tema SIGTAE basado en variables CSS (navy/petrol/cyan), sidebar “pill”, cards KPI, badges normalizadas de estado y prioridad.

| Página | Ruta | Descripción |
| --- | --- | --- |
| Entrada | `/index.php` | Redirige a login o dashboard según sesión. |
| Login | `/login.php` | Validación contra AD con fallback local. |
| Dashboard | `/dashboard.php` | KPIs, gráficas (estado, prioridad, oficina, ranking, desempeño por oficina) y panel lateral con “Próximas por vencer”, “Últimas evidencias”, “Top desempeño”. |
| Mis tareas | `/mis-tareas.php` | Tabla de tareas asignadas al usuario actual (DataTables, filtros, días restantes con semáforo). |
| Asignar tarea | `/asignar-tarea.php` | Formulario de alta (título, descripción, responsable, prioridad, modalidad, fecha límite, categoría). |
| Detalle de tarea | `/tarea.php?id=...` | Detalle + *Presentar evidencia* (múltiples archivos) + *Evaluación* + *Gestión* (cancelar/reasignar) + Historial lateral. |
| Seguimiento | `/seguimiento.php` | Vista transversal de tareas (filtros y bulk view). |
| Tareas por oficina | `/tareas-metrologia.php`, `/tareas-preparacion.php` | Subconjunto por oficina específica. |
| Reportes | `/reportes.php` | Reportes agregados y exportables. |
| Evaluación | `/evaluacion.php` | Bandeja de tareas pendientes de evaluar por el usuario. |
| Historial | `/historial.php` | Bitácora global de eventos, con filtros. |
| Ranking | `/ranking.php` | Desempeño por colaborador. |
| Calendario | `/calendario.php` | Vista calendario con fechas límite. |
| Metrología › Dashboard | `/metrologia-dashboard.php` | KPIs del flujo de calibración (expedientes por estado, atrasos, pendientes por zona) con filtros (año/mes/zona/estado/técnico). |
| Metrología › Solicitudes | `/metrologia-solicitudes.php` | Captura rápida de **solicitudes recibidas** y conversión a **expediente** (según permisos). |
| Metrología › SIG | `/metrologia-sig.php` | Módulo embebido `public/metrologia/sig/` con sus propios assets y APIs (documentos, secciones, nodos, no conformidades). |
| Admin › Usuarios | `/admin-usuarios.php` | CRUD de usuarios (solo super admin). |
| Admin › Oficinas | `/admin-oficinas.php` | CRUD de oficinas (solo super admin). |
| Admin › Delegaciones | `/admin-delegaciones.php` | Delegaciones temporales por ausencia. |
| Descarga evidencia | `/evidencia.php?task_id&ev_id` | Entrega de archivo validando permisos. |
| Seed demo | `/seed-tasks.php` | Carga tareas de ejemplo desde `tasks_seed.json` (idempotente). |
| Cerrar sesión | `/logout.php` | Destruye sesión y regresa a login. |

### 7.1 Endpoints internos (JSON)

| Endpoint | Ruta | ¿Para qué? |
| --- | --- | --- |
| API de tareas (dashboard) | `/api/tareas.php` | Alimenta los modales interactivos del dashboard (filtros por estado/prioridad/oficina/responsable). |
| API de usuarios (admin) | `/api/usuarios.php` | Utilidad administrativa: consulta por RPE vía proxy interno (evita CORS desde el navegador). |

---

## 8. Estilo de arquitectura y desarrollo (cómo está construido)

SIGTAE no es un framework, pero sigue un estilo consistente:

- **“Controlador” delgado por página**: cada archivo en `public/*.php` arma el contenedor (`app/bootstrap.php`), valida sesión/permisos, prepara datos y renderiza una vista de `views/`.
- **Lógica en servicios**: reglas de negocio y cálculos viven en `app/Services/*` (estados, permisos, desempeño, historial, autenticación, etc.).
- **Persistencia por repositorios**: acceso a datos encapsulado en `app/Repositories/*` (implementaciones `*Json` usando `app/Core/JsonStorage.php` para lectura/escritura atómica).
- **Layout único y UI compartida**: `views/layout.php` define navegación, tema visual y helpers JS (tooltips, modal global, PJAX). Las vistas solo inyectan contenido.
- **Base path robusto**: el `bootstrap` calcula `base_path` para funcionar en subdirectorios y con rewrite a `public/`.

---

## 9. Módulos del sistema (qué hace cada uno)

### 9.1 Módulo administrativo: Compromisos (Tareas)

- **Propósito**: asignar tareas, recibir evidencias, evaluar y medir desempeño (KPI/ranking).
- **Datos**: `tasks.json`, `history.json`, `users.json`, `offices.json`, `departments.json`, `delegations.json`.
- **Servicios clave**:
  - `TaskStateService`: calcula estados y transiciones por fechas/evidencias.
  - `PermissionService`: reglas para asignar/evaluar/cancelar/reasignar.
  - `PerformanceService`: ranking y desempeño.
  - `HistoryService`: bitácora de eventos.

### 9.2 Módulo administrativo: Evidencias

- **Propósito**: adjuntar evidencia (archivos) a una tarea y consultarla con control de permisos.
- **Almacenamiento**: `storage/uploads/tareas/<id_tarea>/...`
- **Entrega**: `public/evidencia.php` valida permiso antes de servir el archivo.

### 9.3 Módulo administrativo: Evaluación

- **Propósito**: bandeja de tareas pendientes; dictamina satisfactoria/insatisfactoria y considera “en tiempo / fuera de tiempo”.
- **Regla de oro**: el estado no se captura manualmente (se calcula).

### 9.4 Módulo administrativo: Reportes / Historial / Ranking / Calendario

- **Propósito**:
  - Reportes agregados (por oficina, estado, etc.).
  - Historial global de eventos.
  - Ranking por desempeño.
  - Calendario de fechas límite.

### 9.5 Módulo Administración (super admin)

- **Usuarios**: alta/edición y utilidades de consulta por RPE (vía `/api/usuarios.php`).
- **Oficinas**: catálogo y estructura organizacional.
- **Delegaciones**: reglas temporales por ausencia (afecta permisos/asignación).

### 9.6 Módulo Metrología (Fase 1)

- **Propósito**: gestión operativa de **solicitudes** y **expedientes** (calibración), con KPIs por estado y filtros.
- **Permisos**: controlado por `MetrologiaPermissionService` (acceso/gestión).
- **Datos**:
  - `metrologia_solicitudes.json`: solicitudes recibidas.
  - `metrologia_expedientes.json`: expedientes derivados.
  - `metrologia_catalogos.json`: zonas/áreas/oficinas/signatarios, etc.
  - `metrologia_historial.json`: eventos del módulo.
- **Servicios/repositorios clave**:
  - `MetrologiaSolicitudRepositoryJson`, `MetrologiaExpedienteRepositoryJson`
  - `MetrologiaFolioService`: normaliza/evita duplicados y genera folios.
  - `MetrologiaHistoryService`: bitácora del módulo.

### 9.7 Submódulo Metrología: SIG (embebido)

- **Propósito**: integrar una vista tipo “explorador SIG” servida desde `public/metrologia/sig/` con APIs internas:
  - `public/metrologia/sig/api/documents.php`
  - `public/metrologia/sig/api/sections.php`
  - `public/metrologia/sig/api/nodes.php`
  - `public/metrologia/sig/api/noconformidades.php`
  - `public/metrologia/sig/api/document_inline.php` / `document_delete.php`
- **Front**: assets propios (CSS/JS) cargados solo en `metrologia-sig.php`.

---

## 10. Casos de uso típicos

1. **Jefe de oficina asigna una revisión semanal** a un supervisor → estado `asignada` → se vuelve `en_proceso`.
2. El supervisor **presenta evidencia** (sube 3 fotos + un PDF en una sola carga).
3. Si está dentro del plazo → pasa a `atendida` y el jefe evalúa → `satisfactoria` (100%).
4. Si lo sube después → queda en `vencida` hasta evaluar → `satisfactoria_fuera_tiempo` (50%).
5. Si nunca sube nada → al vencer pasa a `incumplimiento` → `no_presentada` (0%).
6. Una tarea puede **reasignarse** a otro colaborador (con permisos) o **cancelarse** con motivo (solo por quienes asignan tareas o super admin).
7. El dashboard refleja todo en tiempo real: KPIs, ranking, desempeño por oficina.

---

## 11. Estructura del proyecto

```text
sigtae/
├── app/
│   ├── bootstrap.php           # Contenedor de dependencias y autoload
│   ├── config/
│   │   ├── app.php             # Configuración (paths, AD, zona horaria)
│   │   └── constants.php       # Niveles, estados, prioridades, eventos, dictámenes
│   ├── Core/
│   │   └── JsonStorage.php     # Lectura/escritura atómica de JSON
│   ├── Repositories/           # Interfaces + *Json (User, Task, History, Office, Department, Delegation)
│   └── Services/
│       ├── AuthService.php               # Sesión + login AD + fallback local
│       ├── AdDirectoryValidationService.php  # Cliente de la API corporativa
│       ├── PermissionService.php         # Reglas de permisos (asignar, evaluar, cancelar, reasignar)
│       ├── TaskStateService.php          # Cálculo de estado y fechas
│       ├── PerformanceService.php        # Ranking y desempeño
│       ├── HistoryService.php            # Bitácora de eventos
│       └── UserAdminGuard.php            # Resguardo de acciones administrativas
├── public/                     # Punto de entrada web (DocumentRoot)
│   ├── api/                     # Endpoints internos JSON (dashboard/admin)
│   └── metrologia/sig/          # Submódulo embebido SIG + APIs
├── views/                      # Plantillas PHP (layout + páginas)
├── storage/
│   ├── json/                   # users, tasks, offices, departments, history, delegations, catalogs
│   └── uploads/                # Evidencias (se crea al subir archivos)
├── Login/                      # Código legado del login (se conserva como referencia)
├── .htaccess                   # RewriteRule → public/ + límites PHP para uploads
├── composer.json
└── README.md
```

---

## 12. Cómo ejecutar en local

### Opción A — servidor embebido de PHP

```powershell
cd "C:\Users\leonm\Documents\01 Desarrollos\PHP\Laboratorio\sigtae"
php -S localhost:8000 -t public
```

Abrir **http://localhost:8000**.

### Opción B — XAMPP / Laragon / Apache

Apuntar el *VirtualHost* a la carpeta raíz del proyecto; el `.htaccess` redirige todo a `public/`.

### Autoload

Si existe `vendor/`, se puede correr `composer dump-autoload`. Si no, `bootstrap.php` registra un autoload manual para `App\*` — no es obligatorio Composer.

---

## 13. Acceso de prueba (piloto)

- **Login:** RPE del usuario (por ejemplo `9L7R4` — Erick Díaz, Jefe de Departamento).
- **Contraseña (ambiente de desarrollo):** `password`.
- Usuarios semilla en `storage/json/users.json` (1 jefe de depto, varios jefes de oficina y supervisores).

Para cargar tareas de ejemplo basta con visitar `/seed-tasks.php` **una sola vez** con sesión iniciada.

---

## 14. Migración futura a base de datos

La lógica de negocio vive en `app/Services/*`, **sin acoplarse a JSON**. Para migrar a MySQL/PostgreSQL:

1. Crear implementaciones `UserRepositoryMysql`, `TaskRepositoryMysql`, etc. que implementen las mismas interfaces (`UserRepositoryInterface`, `TaskRepositoryInterface`, …).
2. Cambiar los bindings en `app/bootstrap.php`.
3. Los servicios y las vistas no requieren cambios.

---

## 15. Convenciones

- Zona horaria: `America/Mexico_City` (en `app/config/app.php`).
- Toda fecha guardada es ISO 8601 con offset (`DateTimeImmutable->format('c')`).
- Los IDs son cadenas cortas (`usr-xxx`, `of-xxx`, `dep-xxx`, `tar-xxx`, `ev-xxx`, `hist-xxx`).
- Los estados **no se capturan manualmente** (excepto `cancelada`): se recalculan con `TaskStateService::computeState` cada que se lee una tarea.
- Nada de lógica basada en el nombre del cargo: todo va por `nivel_jerarquico`, `puede_asignar`, `alcance_asignacion`, `oficina_id`, `departamento_id`, `es_super_admin`.

---

## 16. Roadmap / pendientes

Consultar `PENDIENTES.md` para el backlog detallado (rediseño con sidebar tipo AdminLTE, módulo de reportes exportables, integración completa con AD, migración a BD, etc.).
