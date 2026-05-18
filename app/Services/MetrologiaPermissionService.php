<?php
namespace App\Services;

/**
 * Permisos del módulo Metrología (Fase 1).
 *
 * Reglas simples y extensibles:
 * - Super admin: todo.
 * - Jefe de oficina Metrología (of-metro) o usuario con puede_asignar en of-metro: asigna/gestiona.
 * - Técnicos (nivel 3) en of-metro: capturan calibración e informe diario.
 * - Signatarios: se modelan explícitamente en metrologia_catalogos.json (rpe) y pueden autorizar.
 */
class MetrologiaPermissionService
{
    private array $catalogos;
    private array $equiposAdminsRpe = ['G46B8', '9MMUY', '9L3DR'];

    public function __construct(array $catalogos)
    {
        $this->catalogos = $catalogos;
    }

    public function canAccess(?array $user): bool
    {
        if (!$user) return false;
        if (!empty($user['es_super_admin'])) return true;
        // Usuarios del laboratorio
        if (($user['oficina_id'] ?? '') === 'of-metro') return true;
        // Usuarios de zona (solo ven Solicitudes)
        return !empty($user['es_usuario_zona']);
    }

    public function canManage(?array $user): bool
    {
        if (!$user) return false;
        if (!empty($user['es_super_admin'])) return true;
        // Gestión del módulo (Calibración): usuarios del laboratorio de Metrología.
        // - Jefaturas / usuarios con puede_asignar en Metrología: gestionan.
        // - Técnicos/supervisores (nivel 3) en Metrología: pueden registrar/editar en Calibración.
        if (($user['oficina_id'] ?? '') !== 'of-metro') return false;
        if (!empty($user['puede_asignar'])) return true;
        $nivel = (int)($user['nivel_jerarquico'] ?? 999);
        return $nivel >= 3;
    }

    public function canCaptureCalibration(?array $user): bool
    {
        if (!$user) return false;
        if (!empty($user['es_super_admin'])) return true;
        return ($user['oficina_id'] ?? '') === 'of-metro' && ((int)($user['nivel_jerarquico'] ?? 999) >= 3);
    }

    public function canAuthorize(?array $user): bool
    {
        if (!$user) return false;
        if (!empty($user['es_super_admin'])) return true;
        $rpe = (string)($user['rpe'] ?? '');
        foreach (($this->catalogos['signatarios'] ?? []) as $s) {
            if (!empty($s['activo']) && ($s['rpe'] ?? '') === $rpe) {
                return true;
            }
        }
        return false;
    }

    /** Devuelve true si el usuario es de zona (solo puede crear solicitudes, no gestionar). */
    public function isZonaUser(?array $user): bool
    {
        if (!$user) return false;
        return !empty($user['es_usuario_zona']);
    }

    /** Prefijo de zona del usuario (ej. "dm21"). Vacío si no es usuario de zona. */
    public function getZonaPrefijo(?array $user): string
    {
        if (!$user) return '';
        return strtolower(trim((string)($user['zona_prefijo'] ?? '')));
    }

    /**
     * Administración del catálogo maestro de equipos.
     * Permite: admins del sistema y RPEs explícitos (Alba, Carlos).
     */
    public function canAdminEquiposCatalogo(?array $user): bool
    {
        if (!$user) return false;
        if (!empty($user['es_super_admin'])) return true;
        $rpe = strtoupper(trim((string)($user['rpe'] ?? '')));
        return in_array($rpe, $this->equiposAdminsRpe, true);
    }
}

