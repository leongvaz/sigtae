<?php
namespace App\Services;

use App\Repositories\MetrologiaExpedienteRepositoryInterface;
use App\Repositories\MetrologiaSolicitudRepositoryInterface;

/**
 * Manejo robusto de folios Metrología.
 *
 * - Generación automática sugerida (repo->nextFolio)
 * - Corrección manual controlada
 * - Validación de duplicados cruzada (solicitudes + expedientes)
 * - Bitácora de corrección se registra en el expediente y/o en historial del módulo
 */
class MetrologiaFolioService
{
    private MetrologiaSolicitudRepositoryInterface $solRepo;
    private MetrologiaExpedienteRepositoryInterface $expRepo;
    private MetrologiaFolioNormalizer $folioNormalizer;

    public function __construct(
        MetrologiaSolicitudRepositoryInterface $solRepo,
        MetrologiaExpedienteRepositoryInterface $expRepo,
        ?MetrologiaFolioNormalizer $folioNormalizer = null
    ) {
        $this->solRepo = $solRepo;
        $this->expRepo = $expRepo;
        $this->folioNormalizer = $folioNormalizer ?? new MetrologiaFolioNormalizer();
    }

    public function isDuplicate(string $folio, ?string $excludeSolicitudId = null, ?string $excludeExpedienteId = null): bool
    {
        $folio = trim($folio);
        if ($folio === '') return false;
        if ($this->solRepo->existsFolio($folio, $excludeSolicitudId)) return true;
        if ($this->expRepo->existsFolio($folio, $excludeExpedienteId)) return true;
        return false;
    }

    public function suggest(int $year): string
    {
        // Los repos usan MET-AAAA-NNNN
        return $this->expRepo->nextFolio($year);
    }

    public function normalize(string $folio): string
    {
        return $this->folioNormalizer->normalize($folio);
    }
}

