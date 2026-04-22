<?php
namespace App\Services;

/**
 * Reglas de acceso al módulo de administración de usuarios y oficinas.
 */
class UserAdminGuard
{
    public static function isSuperAdmin(?array $user): bool
    {
        return !empty($user['es_super_admin']);
    }

    public static function canAccessUserAdmin(?array $actor): bool
    {
        if (!$actor || empty($actor['activo'] ?? true)) {
            return false;
        }
        return self::isSuperAdmin($actor);
    }

    public static function canAccessOfficeAdmin(?array $actor): bool
    {
        return self::isSuperAdmin($actor);
    }

    public static function canEditUser(?array $actor, ?array $target): bool
    {
        if (!$actor || !$target) {
            return false;
        }
        if (self::isSuperAdmin($actor)) {
            return true;
        }
        if (!self::canAccessUserAdmin($actor)) {
            return false;
        }
        $oid = trim((string)($actor['oficina_id'] ?? ''));
        if ($oid === '' || (int)($target['nivel_jerarquico'] ?? 0) !== 3) {
            return false;
        }
        if (($target['oficina_id'] ?? '') !== $oid) {
            return false;
        }
        return ($target['supervisor_id'] ?? '') === ($actor['id'] ?? '');
    }

    public static function canDeleteUser(?array $actor, ?array $target): bool
    {
        if (!$actor || !$target) {
            return false;
        }
        if (!self::isSuperAdmin($actor)) {
            return false;
        }
        return ($actor['id'] ?? '') !== ($target['id'] ?? '');
    }
}
