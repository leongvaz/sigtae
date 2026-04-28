<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class MetrologiaSolicitudRepositoryJson implements MetrologiaSolicitudRepositoryInterface
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
        $pred = function(array $s) use ($filters): bool {
            if (!empty($filters['anio'])) {
                $y = (int)substr((string)($s['fecha_solicitud'] ?? ''), 0, 4);
                if ($y !== (int)$filters['anio']) return false;
            }
            foreach (['mes' => 'fecha_solicitud'] as $k => $field) {
                if (!empty($filters[$k])) {
                    $m = (int)substr((string)($s[$field] ?? ''), 5, 2);
                    if ($m !== (int)$filters[$k]) return false;
                }
            }
            foreach (['zona_id','area_id','oficina_id','estado'] as $k) {
                if (!empty($filters[$k]) && (($s[$k] ?? '') !== $filters[$k])) return false;
            }
            if (!empty($filters['folio']) && stripos((string)($s['folio'] ?? ''), (string)$filters['folio']) === false) return false;
            if (!empty($filters['serie']) && stripos((string)($s['no_serie'] ?? ''), (string)$filters['serie']) === false) return false;
            return true;
        };
        $out = array_values(array_filter($list, $pred));
        usort($out, fn($a,$b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
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
        $entity['id'] = $id ?? MetrologiaRepositoryUtils::newId('msol');
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
        foreach ($list as $s) {
            if (($s['folio'] ?? '') === $folio) {
                if ($excludeId && ($s['id'] ?? '') === $excludeId) continue;
                return true;
            }
        }
        return false;
    }

    public function nextFolio(int $year): string
    {
        $list = $this->storage->read([]);
        $max = 0;
        foreach ($list as $s) {
            $folio = (string)($s['folio'] ?? '');
            if (preg_match('/^MET-' . $year . '-(\d+)$/', $folio, $m)) {
                $n = (int)$m[1];
                if ($n > $max) $max = $n;
            }
        }
        return MetrologiaRepositoryUtils::buildFolio('MET', $year, $max + 1);
    }
}

