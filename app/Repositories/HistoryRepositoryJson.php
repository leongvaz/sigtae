<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class HistoryRepositoryJson implements HistoryRepositoryInterface
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
    }

    public function find(string $id): ?array
    {
        $list = $this->storage->read([]);
        foreach ($list as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return $this->storage->read([]);
    }

    public function findByTask(string $tareaId): array
    {
        $list = $this->storage->read([]);
        $out = array_values(array_filter($list, fn($h) => ($h['tarea_id'] ?? '') === $tareaId));
        usort($out, fn($a, $b) => strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? ''));
        return $out;
    }

    public function findByUser(string $usuarioId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($h) => ($h['usuario_id'] ?? '') === $usuarioId));
    }

    public function findGlobal(array $filters = []): array
    {
        $list = $this->storage->read([]);
        if (!empty($filters['tarea_id'])) {
            $list = array_values(array_filter($list, fn($h) => ($h['tarea_id'] ?? '') === $filters['tarea_id']));
        }
        if (!empty($filters['usuario_id'])) {
            $list = array_values(array_filter($list, fn($h) => ($h['usuario_id'] ?? '') === $filters['usuario_id']));
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
        usort($list, fn($a, $b) => strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? ''));
        return $list;
    }

    public function add(array $entry): array
    {
        $list = $this->storage->read([]);
        $entry['id'] = $entry['id'] ?? 'evt-' . bin2hex(random_bytes(6));
        $entry['fecha_hora'] = $entry['fecha_hora'] ?? (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
        $list[] = $entry;
        $this->storage->write($list);
        return $entry;
    }
}
