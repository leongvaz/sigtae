<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class MetrologiaExpedienteRepositoryJson implements MetrologiaExpedienteRepositoryInterface
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
                $y = (int)substr((string)($e['fecha_solicitud'] ?? $e['created_at'] ?? ''), 0, 4);
                if ($y !== (int)$filters['anio']) return false;
            }
            if (!empty($filters['mes'])) {
                $m = (int)substr((string)($e['fecha_solicitud'] ?? $e['created_at'] ?? ''), 5, 2);
                if ($m !== (int)$filters['mes']) return false;
            }
            foreach (['zona_id','area_id','oficina_id','estado_expediente','tecnico_rpe'] as $k) {
                if (!empty($filters[$k]) && (($e[$k] ?? '') !== $filters[$k])) return false;
            }
            if (!empty($filters['folio']) && stripos((string)($e['folio'] ?? ''), (string)$filters['folio']) === false) return false;
            if (!empty($filters['serie']) && stripos((string)($e['no_serie'] ?? ''), (string)$filters['serie']) === false) return false;
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
        $entity['id'] = $id ?? MetrologiaRepositoryUtils::newId('mexp');
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

    public function nextFolio(int $year): string
    {
        $list = $this->storage->read([]);
        $max = 0;
        foreach ($list as $e) {
            $folio = (string)($e['folio'] ?? '');
            if (preg_match('/^MET-' . $year . '-(\d+)$/', $folio, $m)) {
                $n = (int)$m[1];
                if ($n > $max) $max = $n;
            }
        }
        return MetrologiaRepositoryUtils::buildFolio('MET', $year, $max + 1);
    }

    public function updateFolio(string $id, string $newFolio, array $meta = []): array
    {
        $list = $this->storage->read([]);
        foreach ($list as $i => $e) {
            if (($e['id'] ?? '') === $id) {
                $e['folio_original'] = $e['folio_original'] ?? ($e['folio'] ?? '');
                $e['folio'] = $newFolio;
                $e['folio_correcciones'] = $e['folio_correcciones'] ?? [];
                $e['folio_correcciones'][] = [
                    'fecha' => MetrologiaRepositoryUtils::nowIso(),
                    'antes' => $e['folio_original'] ?? '',
                    'despues' => $newFolio,
                    'meta' => $meta,
                ];
                $e['updated_at'] = MetrologiaRepositoryUtils::nowIso();
                $list[$i] = $e;
                $this->storage->write($list);
                return $e;
            }
        }
        throw new \RuntimeException('Expediente no encontrado para corrección de folio.');
    }
}

