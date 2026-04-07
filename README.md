# SIGTAE — Sistema de Gestión de Tareas

Sistema interno para **asignación, seguimiento, evidencia y evaluación de tareas** en una estructura jerárquica (Departamento de Laboratorio de Metrología).

## Stack

- **Backend:** PHP puro
- **Frontend:** HTML, CSS, Bootstrap 5, JavaScript
- **Tablas:** DataTables
- **Gráficas:** Chart.js
- **Persistencia:** archivos JSON (preparado para migrar a BD)

## Estructura del proyecto

```
├── app/
│   ├── config/          # app.php, constants.php
│   ├── Core/            # JsonStorage (lectura/escritura JSON)
│   ├── Repositories/    # Interfaces + *Json (User, Task, History, Delegation, Office, Department)
│   ├── Services/        # Auth, Permission, TaskState, Performance, History
│   └── bootstrap.php     # Contenedor de dependencias
├── public/              # Punto de entrada web
│   ├── index.php        # Redirige a login o dashboard
│   ├── login.php
│   ├── dashboard.php
│   ├── mis-tareas.php
│   ├── asignar-tarea.php
│   ├── tarea.php        # Detalle + evidencias + evaluación
│   ├── seguimiento.php
│   ├── evaluacion.php
│   ├── historial.php
│   ├── ranking.php
│   ├── calendario.php
│   ├── admin-usuarios.php
│   ├── admin-delegaciones.php
│   ├── seed-tasks.php   # Carga tareas de ejemplo (una vez)
│   └── logout.php
├── storage/
│   ├── json/            # users.json, tasks.json, history.json, delegations.json, offices.json, departments.json, catalogs.json
│   └── uploads/         # (futuro) archivos de evidencia
├── views/               # Vistas PHP (layout + páginas)
├── composer.json
└── README.md
```

## Cómo ejecutar

1. **Requisitos:** PHP 8.0+ (con extensiones json, session).

2. **Servidor local (recomendado):**
   ```bash
   cd "C:\Users\leonm\Documents\01 Desarrollos\PHP\Laboratorio\seguimiento tareas"
   php -S localhost:8000 -t public
   ```
   Abrir en el navegador: **http://localhost:8000**

3. **Autoload (opcional):** Si existe `vendor/`:
   ```bash
   composer dump-autoload
   ```
   Si no, el `bootstrap.php` usa un autoload manual para las clases `App\*`.

## Acceso (piloto)

- **Login:** RPE. Ejemplo: `9L7R4` (Erick Díaz, Jefe de Departamento).
- **Contraseña (todos los usuarios de ejemplo):** `password`

Usuarios semilla en `storage/json/users.json` (Jefe de Departamento, Jefes de Oficina, Supervisores con adscripción).

## Funcionalidad principal

- **Jerarquía y permisos:** Niveles 1–3 (Jefe Dept, Jefe Oficina, Supervisor). Asignación según `alcance_asignacion` y ámbito (oficina/departamento). No se usan condicionales por nombre de cargo.
- **Estados de tarea:** Calculados automáticamente: Asignada → En proceso / Incumplimiento / Vencida / Atendida según fecha límite y evidencias.
- **Evidencias:** Versionado (v1, v2…); por ahora solo texto/descripción (archivos preparado para producción).
- **Evaluación:** Dictámenes: satisfactoria, satisfactoria_fuera_tiempo, requiere_correccion, no_presentada. % cumplimiento: 100% en tiempo, 50% fuera, 0% no presentada.
- **Desempeño:** Promedio simple por colaborador. Dashboard con KPIs, gráficas (estado, prioridad, oficina, ranking).
- **Historial:** Eventos por tarea y global, con filtros.

## Migración a base de datos

Sustituir en `bootstrap.php` los repositorios JSON por implementaciones que usen PDO/MySQL (por ejemplo `UserRepositoryMysql`, `TaskRepositoryMysql`), manteniendo las mismas interfaces. La lógica de negocio en Services no cambia.

## Datos semilla

- **Usuarios:** Definidos en `storage/json/users.json` (piloto Laboratorio de Metrología).
- **Tareas:** Opcional. Visitar una vez `/seed-tasks.php` (con sesión iniciada) para cargar tareas de ejemplo desde `storage/json/tasks_seed.json`. Si ya hay tareas, no se sobrescriben.
