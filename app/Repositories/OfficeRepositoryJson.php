<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class OfficeRepositoryJson implements OfficeRepositoryInterface
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

    public function findByDepartment(string $departamentoId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($o) => ($o['departamento_id'] ?? '') === $departamentoId));
    }

    public function save(array $office): array
    {
        $list = $this->storage->read([]);
        $id = $office['id'] ?? null;
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
        if ($id) {
            foreach ($list as $i => $item) {
                if (($item['id'] ?? '') === $id) {
                    $office['updated_at'] = $now;
                    $list[$i] = $office;
                    $this->storage->write($list);
                    return $office;
                }
            }
        }
        if (empty($office['id'])) {
            $office['id'] = 'of-' . bin2hex(random_bytes(3));
        }
        $office['created_at'] = $office['created_at'] ?? $now;
        $office['updated_at'] = $now;
        $list[] = $office;
        $this->storage->write($list);
        return $office;
    }

    public function delete(string $id): bool
    {
        $list = $this->storage->read([]);
        $newList = array_values(array_filter($list, fn($o) => ($o['id'] ?? '') !== $id));
        if (count($newList) === count($list)) {
            return false;
        }
        $this->storage->write($newList);
        return true;
    }
}
