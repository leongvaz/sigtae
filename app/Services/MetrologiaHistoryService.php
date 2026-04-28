<?php
namespace App\Services;

use App\Core\JsonStorage;
use App\Repositories\MetrologiaRepositoryUtils;

/**
 * Bitácora del módulo Metrología (por expediente).
 * Se mantiene separada de HistoryService de tareas.
 */
class MetrologiaHistoryService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
        $this->storage->ensureExists([]);
    }

    public function log(
        ?string $expedienteId,
        string $actorUserId,
        string $tipo,
        string $descripcion,
        array $meta = []
    ): array {
        $list = $this->storage->read([]);
        $evt = [
            'id' => MetrologiaRepositoryUtils::newId('mhe'),
            'expediente_id' => $expedienteId,
            'actor_user_id' => $actorUserId,
            'tipo_evento' => $tipo,
            'descripcion' => $descripcion,
            'metadata' => $meta,
            'fecha_hora' => MetrologiaRepositoryUtils::nowIso(),
        ];
        $list[] = $evt;
        $this->storage->write($list);
        return $evt;
    }

    public function getByExpediente(string $expedienteId): array
    {
        $list = $this->storage->read([]);
        $out = array_values(array_filter($list, fn($h) => ($h['expediente_id'] ?? '') === $expedienteId));
        usort($out, fn($a,$b) => strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? ''));
        return $out;
    }

    public function findGlobal(array $filters = []): array
    {
        $list = $this->storage->read([]);
        if (!empty($filters['folio'])) {
            $needle = (string)$filters['folio'];
            $list = array_values(array_filter($list, fn($h) => stripos((string)($h['metadata']['folio'] ?? ''), $needle) !== false));
        }
        if (!empty($filters['expediente_id'])) {
            $list = array_values(array_filter($list, fn($h) => ($h['expediente_id'] ?? '') === $filters['expediente_id']));
        }
        if (!empty($filters['tipo_evento'])) {
            $list = array_values(array_filter($list, fn($h) => ($h['tipo_evento'] ?? '') === $filters['tipo_evento']));
        }
        if (!empty($filters['desde'])) {
            $list = array_values(array_filter($list, fn($h) => ($h['fecha_hora'] ?? '') >= $filters['desde']));
        }
        if (!empty($filters['hasta'])) {
            $list = array_values(array_filter($list, fn($h) => ($h['fecha_hora'] ?? '') <= $filters['hasta']));
        }
        usort($list, fn($a,$b) => strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? ''));
        return $list;
    }
}

