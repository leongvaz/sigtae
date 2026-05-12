<?php
namespace App\Services;

/**
 * Control de acceso para el módulo AMI.
 */
class AmiGuard
{
    /**
     * Por el momento, acceso restringido a:
     * - super admin
     * - RPEs explícitos (temporal)
     */
    public static function canAccess(?array $user): bool
    {
        if (!$user) return false;
        if (!empty($user['es_super_admin'])) return true;
        $rpe = strtoupper(trim((string)($user['rpe'] ?? '')));
        return in_array($rpe, ['9NKJ6', '9L7R4', 'G46BG', 'G44BR', '9NKTL'], true);
    }
}

