<?php
namespace App\Services;

use App\Repositories\HistoryRepositoryInterface;

class HistoryService
{
    private HistoryRepositoryInterface $historyRepo;

    public function __construct(HistoryRepositoryInterface $historyRepo)
    {
        $this->historyRepo = $historyRepo;
    }

    public function log(
        ?string $tareaId,
        string $usuarioId,
        string $tipoEvento,
        string $descripcion,
        array $metadata = []
    ): array {
        return $this->historyRepo->add([
            'tarea_id' => $tareaId,
            'usuario_id' => $usuarioId,
            'tipo_evento' => $tipoEvento,
            'descripcion' => $descripcion,
            'metadata' => $metadata,
        ]);
    }

    public function getByTask(string $tareaId): array
    {
        return $this->historyRepo->findByTask($tareaId);
    }

    public function getGlobal(array $filters = []): array
    {
        return $this->historyRepo->findGlobal($filters);
    }
}
