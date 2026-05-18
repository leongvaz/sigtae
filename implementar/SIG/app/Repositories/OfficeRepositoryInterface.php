<?php
namespace App\Repositories;

interface OfficeRepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;
    public function findByDepartment(string $departamentoId): array;
    public function save(array $office): array;
    public function delete(string $id): bool;
}
