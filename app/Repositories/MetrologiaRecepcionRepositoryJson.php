<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class MetrologiaRecepcionRepositoryJson implements MetrologiaRecepcionRepositoryInterface
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
        $pred = function(array $r) use ($filters): bool {
            if (!empty($filters['anio'])) {
                $y = (int)substr((string)($r['fecha_recepcion'] ?? ''), 0, 4);
                if ($y !== (int)$filters['anio']) return false;
            }
            if (!empty($filters['folio_recepcion']) && stripos((string)($r['folio_recepcion'] ?? ''), (string)$filters['folio_recepcion']) === false) {
                return false;
            }
            if (!empty($filters['estado']) && (($r['estado'] ?? '') !== $filters['estado'])) return false;

            // entrega (zona/área)
            if (!empty($filters['zona_entrega'])) {
                $z = (string)($r['entrega']['zona_id'] ?? $r['entrega']['zona'] ?? '');
                if ($z !== (string)$filters['zona_entrega']) return false;
            }
            if (!empty($filters['area_entrega'])) {
                $a = (string)($r['entrega']['area_id'] ?? $r['entrega']['area'] ?? '');
                if ($a !== (string)$filters['area_entrega']) return false;
            }

            return true;
        };

        $out = array_values(array_filter($list, $pred));
        usort($out, fn($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
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
        $entity['id'] = $id ?? MetrologiaRepositoryUtils::newId('mrec');
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

    public function existsFolioRecepcion(string $folioRecepcion, ?string $excludeId = null): bool
    {
        $folioRecepcion = trim($folioRecepcion);
        if ($folioRecepcion === '') return false;
        $list = $this->storage->read([]);
        foreach ($list as $r) {
            if (($r['folio_recepcion'] ?? '') === $folioRecepcion) {
                if ($excludeId && ($r['id'] ?? '') === $excludeId) continue;
                return true;
            }
        }
        return false;
    }

    public function nextFolioRecepcion(int $year): string
    {
        $list = $this->storage->read([]);
        $max = 0;
        foreach ($list as $r) {
            $folio = (string)($r['folio_recepcion'] ?? '');
            if (preg_match('/^REC-' . $year . '-(\d{4})$/', $folio, $m)) {
                $n = (int)$m[1];
                if ($n > $max) $max = $n;
            }
        }
        return 'REC-' . $year . '-' . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
    }
}

