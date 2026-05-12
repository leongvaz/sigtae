<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class MetrologiaEquipoCatalogoRepositoryJson implements MetrologiaEquipoCatalogoRepositoryInterface
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
        $q = trim((string)($filters['q'] ?? ''));
        $pred = function (array $e) use ($filters, $q): bool {
            foreach (['no_serie','marca','modelo','descripcion','zona','area','oficina'] as $k) {
                if (isset($filters[$k]) && $filters[$k] !== null && $filters[$k] !== '') {
                    $val = (string)$filters[$k];
                    if (stripos((string)($e[$k] ?? ''), $val) === false) return false;
                }
            }
            if ($q !== '') {
                $hay = false;
                foreach (['no_serie','marca','modelo','descripcion','zona','area','oficina','folio'] as $k) {
                    if (stripos((string)($e[$k] ?? ''), $q) !== false) { $hay = true; break; }
                }
                if (!$hay) return false;
            }
            return true;
        };
        $out = array_values(array_filter($list, $pred));
        usort($out, fn($a, $b) => strcmp((string)($a['no_serie'] ?? ''), (string)($b['no_serie'] ?? '')));
        return $out;
    }

    public function save(array $entity): array
    {
        $list = $this->storage->read([]);
        $now = MetrologiaRepositoryUtils::nowIso();
        $id = $entity['id'] ?? null;
        $entity['updated_at'] = $now;
        if ($id) {
            foreach ($list as $i => $item) {
                if (($item['id'] ?? '') === $id) {
                    $entity['created_at'] = $item['created_at'] ?? ($entity['created_at'] ?? $now);
                    $list[$i] = $entity;
                    $this->storage->write($list);
                    return $entity;
                }
            }
        }
        $entity['id'] = $id ?? MetrologiaRepositoryUtils::newId('meq');
        $entity['created_at'] = $entity['created_at'] ?? $now;
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

    public function findSuggestions(string $field, string $q, int $limit = 12): array
    {
        $field = trim($field);
        $q = trim($q);
        $allowed = ['no_serie','marca','modelo','descripcion'];
        if (!in_array($field, $allowed, true) || $q === '') return [];

        $list = $this->storage->read([]);
        $hits = [];
        foreach ($list as $e) {
            $v = (string)($e[$field] ?? '');
            if ($v === '') continue;
            if (stripos($v, $q) === false) continue;
            $hits[] = [
                'id' => $e['id'] ?? '',
                'value' => $v,
                'no_serie' => $e['no_serie'] ?? '',
                'marca' => $e['marca'] ?? '',
                'modelo' => $e['modelo'] ?? '',
                'descripcion' => $e['descripcion'] ?? '',
                'zona' => $e['zona'] ?? '',
                'area' => $e['area'] ?? '',
                'oficina' => $e['oficina'] ?? '',
            ];
            if (count($hits) >= $limit) break;
        }
        return $hits;
    }
}

