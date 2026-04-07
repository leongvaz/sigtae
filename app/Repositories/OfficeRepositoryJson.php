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
}
