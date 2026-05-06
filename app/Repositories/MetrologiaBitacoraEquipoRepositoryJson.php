<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class MetrologiaBitacoraEquipoRepositoryJson implements MetrologiaBitacoraEquipoRepositoryInterface
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
        $this->storage->ensureExists([]);
    }

    public function find(string $id): ?array
    {
        $list = $this->storage->read([]);
        foreach ($list as $item) {
            if (($item['id'] ?? '') === $id) return $item;
        }
        return null;
    }

    public function findAll(): array
    {
        return $this->storage->read([]);
    }

    public function findByFilters(array $filters = []): array
    {
        $list = $this->storage->read([]);
        $pred = function(array $e) use ($filters): bool {
            if (!empty($filters['anio'])) {
                $cand = (string)($e['recibido'] ?? '');
                $y = 0;
                if (preg_match('/^(\d{4})\-\d{2}\-\d{2}$/', $cand, $m)) {
                    $y = (int)$m[1];
                } else {
                    $y = (int)substr((string)($e['folio'] ?? ''), 0, 4);
                }
                if ($y !== (int)$filters['anio']) return false;
            }
            if (!empty($filters['zona'])) {
                $val = (string)$filters['zona'];
                $z = (string)($e['zona_id'] ?? $e['zona'] ?? '');
                if ($z !== $val) return false;
            }
            if (!empty($filters['area'])) {
                $val = (string)$filters['area'];
                $a = (string)($e['area_id'] ?? $e['area'] ?? '');
                if (stripos($a, $val) === false) return false;
            }
            if (!empty($filters['estado'])) {
                $st = (string)($e['estado'] ?? '');
                $wanted = (string)$filters['estado'];
                if ($wanted === 'programado') {
                    if ($st !== 'programado') return false;
                } elseif ($wanted === 'no_programado') {
                    if ($st === 'programado') return false;
                } else {
                    if ($st !== $wanted) return false;
                }
            }
            foreach (['folio' => 'folio', 'serie' => 'no_serie'] as $k => $field) {
                if (!empty($filters[$k]) && stripos((string)($e[$field] ?? ''), (string)$filters[$k]) === false) return false;
            }
            return true;
        };
        $out = array_values(array_filter($list, $pred));
        usort($out, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $out;
    }

    public function save(array $entity): array
    {
        $list = $this->storage->read([]);
        $now = MetrologiaRepositoryUtils::nowIso();
        $id = $entity['id'] ?? null;
        if ($id) {
            foreach ($list as $i => $item) {
                if (($item['id'] ?? '') === $id) {
                    $entity['updated_at'] = $now;
                    $list[$i] = $entity;
                    $this->storage->write($list);
                    return $entity;
                }
            }
        }
        $entity['id'] = $id ?? MetrologiaRepositoryUtils::newId('mbeq');
        $entity['created_at'] = $entity['created_at'] ?? $now;
        $entity['updated_at'] = $now;
        $list[] = $entity;
        $this->storage->write($list);
        return $entity;
    }

    public function delete(string $id): bool
    {
        $list = $this->storage->read([]);
        $newList = array_values(array_filter($list, fn($x) => ($x['id'] ?? '') !== $id));
        if (count($newList) === count($list)) return false;
        $this->storage->write($newList);
        return true;
    }

    public function existsFolio(string $folio, ?string $excludeId = null): bool
    {
        $folio = trim($folio);
        if ($folio === '') return false;
        $list = $this->storage->read([]);
        foreach ($list as $e) {
            if (($e['folio'] ?? '') === $folio) {
                if ($excludeId && ($e['id'] ?? '') === $excludeId) continue;
                return true;
            }
        }
        return false;
    }

    public function findBySerie(string $serie): array
    {
        $serie = trim((string)$serie);
        if ($serie === '') return [];
        $list = $this->storage->read([]);
        $out = array_values(array_filter($list, fn($e) => strcasecmp((string)($e['no_serie'] ?? ''), $serie) === 0));
        usort($out, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $out;
    }
}

