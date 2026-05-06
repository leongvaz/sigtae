<?php
namespace App\Services;

use App\Repositories\MetrologiaBitacoraEquipoRepositoryInterface;
use App\Repositories\MetrologiaRecepcionRepositoryInterface;

/**
 * Folios del módulo de recepción de equipos.
 *
 * - Folio de recepción (cabecera): REC-AAAA-NNNN
 * - Folio por equipo (concentrado): AAAA-NNNN (por año, reinicia)
 *
 * Regla especial:
 * - Si no hay registros del año 2026, iniciar en 2026-0281.
 */
class MetrologiaRecepcionFolioService
{
    private MetrologiaRecepcionRepositoryInterface $recepcionRepo;
    private MetrologiaBitacoraEquipoRepositoryInterface $bitacoraRepo;

    public function __construct(
        MetrologiaRecepcionRepositoryInterface $recepcionRepo,
        MetrologiaBitacoraEquipoRepositoryInterface $bitacoraRepo
    ) {
        $this->recepcionRepo = $recepcionRepo;
        $this->bitacoraRepo = $bitacoraRepo;
    }

    public function nextRecepcionFolio(int $year): string
    {
        return $this->recepcionRepo->nextFolioRecepcion($year);
    }

    public function nextEquipoFolio(int $year): string
    {
        $all = $this->bitacoraRepo->findAll();
        $max = 0;
        $hasYear = false;

        foreach ($all as $e) {
            $folio = (string)($e['folio'] ?? '');
            if (preg_match('/^' . $year . '\-(\d{4})$/', $folio, $m)) {
                $hasYear = true;
                $n = (int)$m[1];
                if ($n > $max) $max = $n;
            }
        }

        if (!$hasYear) {
            $start = ($year === 2026) ? 281 : 1;
            return $year . '-' . str_pad((string)$start, 4, '0', STR_PAD_LEFT);
        }
        return $year . '-' . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genera N folios consecutivos para equipos (por año).
     *
     * @return string[]
     */
    public function nextEquipoFolios(int $year, int $count): array
    {
        $count = max(0, (int)$count);
        if ($count === 0) return [];

        // Tomamos el siguiente y avanzamos localmente para evitar re-escaneo N veces.
        $first = $this->nextEquipoFolio($year);
        if (!preg_match('/^' . $year . '\-(\d{4})$/', $first, $m)) {
            // fallback seguro
            $m = [0, '0001'];
        }
        $n = (int)$m[1];
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = $year . '-' . str_pad((string)($n + $i), 4, '0', STR_PAD_LEFT);
        }
        return $out;
    }
}

