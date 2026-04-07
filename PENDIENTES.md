# SIGTAE — Pendientes

Lista de trabajo pendiente para pasar del **piloto con JSON** a un **entorno estable y productivo**. Orden sugerido por impacto; conviene repriorizar según normativa interna (CFE) y disponibilidad de infraestructura.

---

## Crítico / producción

| Ítem | Descripción | Referencia |
|------|-------------|------------|
| Base de datos | Implementar repositorios PDO (p. ej. `UserRepositoryMysql`, `TaskRepositoryMysql`) y sustituirlos en `app/bootstrap.php`; esquema, migraciones y respaldo. | `README.md` — Migración a base de datos |
| Evidencias con archivo | Subida a `storage/uploads` (ruta ya en `app/config/app.php` → `upload_path`), validación de tipo/tamaño, descarga con control de permisos, metadatos en JSON/BD. En **Agregar evidencia** (`views/tarea.php`): permitir adjuntos **Excel** (p. ej. `.xlsx`, `.xls`), **PDF**, **imágenes** (p. ej. `.jpg`, `.png`) y **presentaciones PowerPoint** (p. ej. `.ppt`, `.pptx`), además del texto actual. | `README.md`, `views/tarea.php` |
| Despliegue documentado | Servidor web (Apache/Nginx), PHP-FPM, HTTPS, permisos de `storage/`, variables de entorno; no depender de `php -S` en producción. | `README.md` — Cómo ejecutar |

---

## Seguridad y cumplimiento

| Ítem | Descripción |
|------|-------------|
| Protección CSRF | Tokens en formularios POST sensibles (asignación, evidencias, evaluación, admin). |
| Sesiones | Tiempo de expiración, regeneración de ID tras login, flags `secure`/`httponly` según HTTPS. |
| Contraseñas en producción | Política de complejidad, flujo de cambio de contraseña; eliminar o rotar usuarios de ejemplo (`password`). |
| Auditoría | Registro de acciones administrativas (altas/bajas usuarios, delegaciones críticas). |
| Integración de identidad | Si aplica: LDAP/SSO corporativo en lugar de solo credenciales locales. |

---

## Funcionalidad y producto

| Ítem | Descripción |
|------|-------------|
| Notificaciones | Avisos por correo (o canal interno) al asignar tarea, cercanía a fecha límite, evaluación pendiente. |
| Exportación | Reportes Excel/PDF de listados (seguimiento, historial, ranking) para jefatura. |
| Búsqueda y filtros | Refinar búsqueda global por folio, filtros guardados o vistas frecuentes. |
| Calendario | Sincronización opcional (iCal) o recordatorios si hay política de uso. |

---

## Calidad y mantenimiento

| Ítem | Descripción |
|------|-------------|
| Pruebas automatizadas | PHPUnit (o similar) sobre servicios: estados de tarea, permisos, reglas de evaluación y desempeño. |
| README operativo | Actualizar ruta de ejemplo en “Cómo ejecutar” al directorio real del equipo; añadir sección “Producción”. |
| Composer / dependencias | Revisar `composer.json`, fijar versiones y documentar instalación en CI o servidor. |

---

## Limpieza del piloto

| Ítem | Descripción |
|------|-------------|
| Datos semilla | `seed-tasks.php` y usuarios de prueba: deshabilitar o proteger en producción. |
| Entorno | `app/config/app.php`: `env` → `production` y revisar `timezone` según región operativa. |

---

## Cómo usar este archivo

- Marcar ítems como hechos al cerrarlos (o moverlos a una sección “Hecho” con fecha).
- Asignar responsable y fecha objetivo en reuniones de seguimiento.
