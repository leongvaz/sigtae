<?php
namespace App\Repositories;

interface DelegationRepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;
    public function findActiveByUser(string $usuarioId): ?array;
    public function findActiveForUser(string $encargadoId): ?array;
    public function save(array $delegation): array;
}
