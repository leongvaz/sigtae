<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class ProgramaAvanceRepositoryJson implements ProgramaAvanceRepositoryInterface
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

    public function findByPrograma(string $programaId): array
    {
        $list = $this->storage->read([]);
        $out = array_values(array_filter($list, function ($x) use ($programaId) {
            return ($x['programa_id'] ?? '') === $programaId;
        }));
        usort($out, function ($a, $b) {
            return strcmp(($a['periodo_fecha'] ?? ''), ($b['periodo_fecha'] ?? ''));
        });
        return $out;
    }

    public function findByActividad(string $actividadId): array
    {
        $list = $this->storage->read([]);
        $out = array_values(array_filter($list, function ($x) use ($actividadId) {
            return ($x['actividad_id'] ?? '') === $actividadId;
        }));
        usort($out, function ($a, $b) {
            return strcmp(($a['periodo_fecha'] ?? ''), ($b['periodo_fecha'] ?? ''));
        });
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

        // Upsert lógico por actividad + fecha + tipo (P/E)
        $actividadId = (string)($entity['actividad_id'] ?? '');
        $periodoFecha = (string)($entity['periodo_fecha'] ?? '');
        $tipo = strtoupper((string)($entity['tipo'] ?? 'P'));
        foreach ($list as $i => $item) {
            if (($item['actividad_id'] ?? '') === $actividadId
                && ($item['periodo_fecha'] ?? '') === $periodoFecha
                && strtoupper((string)($item['tipo'] ?? 'P')) === $tipo
            ) {
                $entity['id'] = $item['id'] ?? MetrologiaRepositoryUtils::newId('pav');
                $entity['created_at'] = $item['created_at'] ?? $now;
                $entity['updated_at'] = $now;
                $list[$i] = $entity;
                $this->storage->write($list);
                return $entity;
            }
        }

        $entity['id'] = $id ?? MetrologiaRepositoryUtils::newId('pav');
        $entity['created_at'] = $entity['created_at'] ?? $now;
        $entity['updated_at'] = $now;
        $list[] = $entity;
        $this->storage->write($list);
        return $entity;
    }

    public function delete(string $id): bool
    {
        $list = $this->storage->read([]);
        $newList = array_values(array_filter($list, function ($x) use ($id) {
            return ($x['id'] ?? '') !== $id;
        }));
        if (count($newList) === count($list)) return false;
        $this->storage->write($newList);
        return true;
    }
}

