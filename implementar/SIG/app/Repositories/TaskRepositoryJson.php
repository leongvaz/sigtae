<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class TaskRepositoryJson implements TaskRepositoryInterface
{
    private JsonStorage $storage;
    private string $keyField = 'id';

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
    }

    public function find(string $id): ?array
    {
        $list = $this->storage->read([]);
        foreach ($list as $item) {
            if (($item[$this->keyField] ?? '') === $id) {
                return $item;
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return $this->storage->read([]);
    }

    public function findByResponsable(string $responsableId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($t) => ($t['responsable_id'] ?? '') === $responsableId));
    }

    public function findByAsignador(string $asignadorId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($t) => ($t['asignador_id'] ?? '') === $asignadorId));
    }

    public function findByOffice(string $oficinaId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($t) => ($t['oficina_id'] ?? '') === $oficinaId));
    }

    public function findByDepartment(string $departamentoId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($t) => ($t['departamento_id'] ?? '') === $departamentoId));
    }

    public function findByEstado(string $estado): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($t) => ($t['estado'] ?? '') === $estado));
    }

    public function save(array $task): array
    {
        $list = $this->storage->read([]);
        $id = $task['id'] ?? null;
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
        if ($id) {
            foreach ($list as $i => $item) {
                if (($item[$this->keyField] ?? '') === $id) {
                    $task['updated_at'] = $now;
                    $list[$i] = $task;
                    $this->storage->write($list);
                    return $task;
                }
            }
        }
        $task['id'] = $id ?? 'task-' . bin2hex(random_bytes(6));
        $task['created_at'] = $task['created_at'] ?? $now;
        $task['updated_at'] = $now;
        $list[] = $task;
        $this->storage->write($list);
        return $task;
    }

    public function delete(string $id): bool
    {
        $list = $this->storage->read([]);
        $newList = array_values(array_filter($list, fn($t) => ($t[$this->keyField] ?? '') !== $id));
        if (count($newList) === count($list)) {
            return false;
        }
        $this->storage->write($newList);
        return true;
    }

    public function nextFolio(): string
    {
        $list = $this->storage->read([]);
        $year = date('Y');
        $max = 0;
        foreach ($list as $t) {
            $folio = $t['folio'] ?? '';
            if (preg_match('/^TAS-' . $year . '-(\d+)$/', $folio, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }
        return 'TAS-' . $year . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
