<?php
namespace App\Repositories;

/**
 * Utilidades compartidas para repositorios del módulo Metrología.
 * Mantiene el mismo estilo del proyecto (arrays + JSON).
 */
final class MetrologiaRepositoryUtils
{
    public static function nowIso(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
    }

    public static function newId(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(6));
    }

    /**
     * Genera folio sugerido tipo MET-AAAA-NNNN.
     * Nota: el módulo permite corrección manual y valida duplicados.
     */
    public static function buildFolio(string $prefix, int $year, int $n): string
    {
        return $prefix . '-' . $year . '-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    }
}

