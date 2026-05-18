<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class DelegationRepositoryJson implements DelegationRepositoryInterface
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

    public function findActiveByUser(string $usuarioId): ?array
    {
        $list = $this->storage->read([]);
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d');
        foreach ($list as $d) {
            if (($d['delegado_id'] ?? '') !== $usuarioId) {
                continue;
            }
            $inicio = $d['fecha_inicio'] ?? '';
            $fin = $d['fecha_fin'] ?? '';
            if ($inicio && $fin && $now >= $inicio && $now <= $fin && ($d['activo'] ?? true)) {
                return $d;
            }
        }
        return null;
    }

    public function findActiveForUser(string $encargadoId): ?array
    {
        $list = $this->storage->read([]);
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d');
        foreach ($list as $d) {
            if (($d['encargado_temporal_id'] ?? '') !== $encargadoId) {
                continue;
            }
            $inicio = $d['fecha_inicio'] ?? '';
            $fin = $d['fecha_fin'] ?? '';
            if ($inicio && $fin && $now >= $inicio && $now <= $fin && ($d['activo'] ?? true)) {
                return $d;
            }
        }
        return null;
    }

    public function save(array $delegation): array
    {
        $list = $this->storage->read([]);
        $id = $delegation['id'] ?? null;
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
        if ($id) {
            foreach ($list as $i => $item) {
                if (($item['id'] ?? '') === $id) {
                    $delegation['updated_at'] = $now;
                    $list[$i] = $delegation;
                    $this->storage->write($list);
                    return $delegation;
                }
            }
        }
        $delegation['id'] = $id ?? 'del-' . bin2hex(random_bytes(4));
        $delegation['created_at'] = $delegation['created_at'] ?? $now;
        $delegation['updated_at'] = $now;
        $list[] = $delegation;
        $this->storage->write($list);
        return $delegation;
    }
}
