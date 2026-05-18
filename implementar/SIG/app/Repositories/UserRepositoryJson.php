<?php
namespace App\Repositories;

use App\Core\JsonStorage;

class UserRepositoryJson implements UserRepositoryInterface
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

    public function findByRpe(string $rpe): ?array
    {
        $list = $this->storage->read([]);
        $rpe = strtoupper(trim($rpe));
        foreach ($list as $item) {
            if (strtoupper(trim($item['rpe'] ?? '')) === $rpe) {
                return $item;
            }
        }
        return null;
    }

    public function findByEmail(string $email): ?array
    {
        $list = $this->storage->read([]);
        $email = strtolower(trim($email));
        foreach ($list as $item) {
            if (strtolower(trim($item['email'] ?? '')) === $email) {
                return $item;
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return $this->storage->read([]);
    }

    public function findByOffice(string $oficinaId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($u) => ($u['oficina_id'] ?? '') === $oficinaId));
    }

    public function findByDepartment(string $departamentoId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($u) => ($u['departamento_id'] ?? '') === $departamentoId));
    }

    public function findSubordinates(string $supervisorId): array
    {
        $list = $this->storage->read([]);
        return array_values(array_filter($list, fn($u) => ($u['supervisor_id'] ?? '') === $supervisorId));
    }

    public function save(array $user): array
    {
        $list = $this->storage->read([]);
        $id = $user['id'] ?? null;
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
        if ($id) {
            foreach ($list as $i => $item) {
                if (($item[$this->keyField] ?? '') === $id) {
                    $user['updated_at'] = $now;
                    $list[$i] = $user;
                    $this->storage->write($list);
                    return $user;
                }
            }
        }
        $user['id'] = $id ?? 'usr-' . bin2hex(random_bytes(4));
        $user['created_at'] = $user['created_at'] ?? $now;
        $user['updated_at'] = $now;
        $list[] = $user;
        $this->storage->write($list);
        return $user;
    }

    public function delete(string $id): bool
    {
        $list = $this->storage->read([]);
        $newList = array_values(array_filter($list, fn($u) => ($u[$this->keyField] ?? '') !== $id));
        if (count($newList) === count($list)) {
            return false;
        }
        $this->storage->write($newList);
        return true;
    }
}
