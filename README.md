# SIGTAE — Sistema de Gestión de Tareas (Laboratorio de Metrología)

**SIGTAE** es un sistema interno para la **asignación, seguimiento, presentación de evidencias y evaluación de tareas** dentro de una estructura jerárquica (Departamento de Laboratorio de Metrología, con oficinas de Metrología, Preparación de Medidores y AMI).

El objetivo es ordenar la operación diaria de un departamento con varias oficinas: que cada jefe pueda asignar tareas a sus colaboradores, dar seguimiento al cumplimiento, recibir evidencias (archivos), evaluar el resultado y medir desempeño con indicadores claros.

---

## 1. Stack tecnológico

| Capa | Tecnología |
| --- | --- |
| Lenguaje backend | PHP 8.0+ (puro, sin framework) |
| Frontend | HTML5, CSS, **Bootstrap 5.3**, JavaScript |
| Tablas interactivas | **DataTables 1.13** (jQuery) |
| Gráficas | **Chart.js 4.4** |
| Iconos | Bootstrap Icons 1.11 |
| Persistencia | Archivos **JSON** en `storage/json/` (diseñado para migrar a BD relacional sin cambiar la lógica) |
| Autenticación | Directorio Activo (API corporativa) con *fallback* a hash local |
| Servidor | Apache (con `.htaccess` de *rewrite*) o `php -S` para desarrollo |

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
| 1 | Jefe de departamento | Jefe Dept. Laboratorio de Metrología |
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
3. Si la API AD no responde y `auth_local_password_fallback = true`, se valida contra el `password_hash` almacenado en `storage/json/users.json` (útil en desarrollo).
4. La sesión se guarda con nombre `sigtae_session` y control de inactividad.

---

## 5. Modelo de datos (JSON)

Archivos en `storage/json/`:

| Archivo | Contenido |
| --- | --- |
| `users.json` | Usuarios: RPE, nombre, cargo, nivel_jerarquico, oficina_id, departamento_id, puede_asignar, alcance_asignacion, es_super_admin, activo. |
| `offices.json` | Oficinas del departamento. |
| `departments.json` | Departamentos (piloto: Laboratorio de Metrología). |
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

Todas las páginas viven en `public/` y usan el *layout* común `views/layout.php` (navbar superior, breadcrumbs, contenedor fluido).

| Página | Ruta | Descripción |
| --- | --- | --- |
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
| Admin › Usuarios | `/admin-usuarios.php` | CRUD de usuarios (solo super admin). |
| Admin › Oficinas | `/admin-oficinas.php` | CRUD de oficinas (solo super admin). |
| Admin › Delegaciones | `/admin-delegaciones.php` | Delegaciones temporales por ausencia. |
| Descarga evidencia | `/evidencia.php?task_id&ev_id` | Entrega de archivo validando permisos. |
| Seed demo | `/seed-tasks.php` | Carga tareas de ejemplo desde `tasks_seed.json` (idempotente). |

---

## 8. Casos de uso típicos

1. **Jefe de oficina asigna una revisión semanal** a un supervisor → estado `asignada` → se vuelve `en_proceso`.
2. El supervisor **presenta evidencia** (sube 3 fotos + un PDF en una sola carga).
3. Si está dentro del plazo → pasa a `atendida` y el jefe evalúa → `satisfactoria` (100%).
4. Si lo sube después → queda en `vencida` hasta evaluar → `satisfactoria_fuera_tiempo` (50%).
5. Si nunca sube nada → al vencer pasa a `incumplimiento` → `no_presentada` (0%).
6. Una tarea puede **reasignarse** a otro colaborador (con permisos) o **cancelarse** con motivo (solo por quienes asignan tareas o super admin).
7. El dashboard refleja todo en tiempo real: KPIs, ranking, desempeño por oficina.

---

## 9. Estructura del proyecto

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

## 10. Cómo ejecutar en local

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

## 11. Acceso de prueba (piloto)

- **Login:** RPE del usuario (por ejemplo `9L7R4` — Erick Díaz, Jefe de Departamento).
- **Contraseña (ambiente de desarrollo):** `password`.
- Usuarios semilla en `storage/json/users.json` (1 jefe de depto, varios jefes de oficina y supervisores).

Para cargar tareas de ejemplo basta con visitar `/seed-tasks.php` **una sola vez** con sesión iniciada.

---

## 12. Migración futura a base de datos

La lógica de negocio vive en `app/Services/*`, **sin acoplarse a JSON**. Para migrar a MySQL/PostgreSQL:

1. Crear implementaciones `UserRepositoryMysql`, `TaskRepositoryMysql`, etc. que implementen las mismas interfaces (`UserRepositoryInterface`, `TaskRepositoryInterface`, …).
2. Cambiar los bindings en `app/bootstrap.php`.
3. Los servicios y las vistas no requieren cambios.

---

## 13. Convenciones

- Zona horaria: `America/Mexico_City` (en `app/config/app.php`).
- Toda fecha guardada es ISO 8601 con offset (`DateTimeImmutable->format('c')`).
- Los IDs son cadenas cortas (`usr-xxx`, `of-xxx`, `dep-xxx`, `tar-xxx`, `ev-xxx`, `hist-xxx`).
- Los estados **no se capturan manualmente** (excepto `cancelada`): se recalculan con `TaskStateService::computeState` cada que se lee una tarea.
- Nada de lógica basada en el nombre del cargo: todo va por `nivel_jerarquico`, `puede_asignar`, `alcance_asignacion`, `oficina_id`, `departamento_id`, `es_super_admin`.

---

## 14. Roadmap / pendientes

Consultar `PENDIENTES.md` para el backlog detallado (rediseño con sidebar tipo AdminLTE, módulo de reportes exportables, integración completa con AD, migración a BD, etc.).
