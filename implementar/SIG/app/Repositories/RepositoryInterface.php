<?php
namespace App\Repositories;

interface RepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;
    public function save(array $entity): array;
    public function delete(string $id): bool;
}
