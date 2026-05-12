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
                $y = 0;
                $cand = (string)($e['recibido'] ?? '');
                if (preg_match('/^(\d{4})\-\d{2}\-\d{2}$/', $cand, $m)) {
                    $y = (int)$m[1];
                } else {
                    $folio = trim((string)($e['folio'] ?? ''));
                    if (preg_match('/^(\d{4})\-\d{1,4}$/', $folio, $m)) {
                        $y = (int)$m[1];
                    } elseif (preg_match('/^(\d{2})\-\d{4}$/', $folio, $m)) {
                        $y = (int)('20' . $m[1]);
                    }
                }
                // Si no podemos inferir año, no filtramos por anio para no ocultar registros.
                if ($y !== 0 && $y !== (int)$filters['anio']) return false;
            }
            if (!empty($filters['zona'])) {
                $val = (string)$filters['zona'];
                $z = (string)($e['zona_id'] ?? $e['zona'] ?? '');
                if (stripos($z, $val) === false) return false;
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
            if (!empty($filters['q'])) {
                $q = (string)$filters['q'];
                $f = (string)($e['folio'] ?? '');
                $s = (string)($e['no_serie'] ?? '');
                if (stripos($f, $q) === false && stripos($s, $q) === false) return false;
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

        // Upsert: si no viene ID pero ya existe un registro para este equipo,
        // actualiza en vez de insertar duplicado.
        if (!$id) {
            $matchIdx = null;
            $recepcionId = trim((string)($entity['recepcion_id'] ?? ''));
            $folio = trim((string)($entity['folio'] ?? ''));
            $serie = strtoupper(trim((string)($entity['no_serie'] ?? '')));

            foreach ($list as $i => $item) {
                $itemSerie = strtoupper(trim((string)($item['no_serie'] ?? '')));
                $itemFolio = trim((string)($item['folio'] ?? ''));
                $itemRecep = trim((string)($item['recepcion_id'] ?? ''));

                // 1) Si viene serie, es el identificador más estable.
                if ($serie !== '' && $itemSerie !== '' && $itemSerie === $serie) { $matchIdx = $i; break; }
                // 2) Si viene recepcion + folio, match exacto.
                if ($matchIdx === null && $recepcionId !== '' && $folio !== '' && $itemRecep === $recepcionId && $itemFolio === $folio) {
                    $matchIdx = $i; break;
                }
                // 3) Si no hay serie, usa folio exacto.
                if ($matchIdx === null && $serie === '' && $folio !== '' && $itemFolio === $folio) { $matchIdx = $i; break; }
            }

            if ($matchIdx !== null) {
                $existing = (array)$list[$matchIdx];
                $entity['id'] = (string)($existing['id'] ?? '');
                $entity['created_at'] = $existing['created_at'] ?? $now;
                $entity['updated_at'] = $now;
                $list[$matchIdx] = $entity;
                $this->storage->write($list);
                return $entity;
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

