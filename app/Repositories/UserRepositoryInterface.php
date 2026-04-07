<?php
namespace App\Repositories;

interface UserRepositoryInterface
{
    public function find(string $id): ?array;
    public function findByRpe(string $rpe): ?array;
    public function findByEmail(string $email): ?array;
    public function findAll(): array;
    public function findByOffice(string $oficinaId): array;
    public function findByDepartment(string $departamentoId): array;
    public function findSubordinates(string $supervisorId): array;
    public function save(array $user): array;
    public function delete(string $id): bool;
}
