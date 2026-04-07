<?php
namespace App\Repositories;

interface HistoryRepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;
    public function findByTask(string $tareaId): array;
    public function findByUser(string $usuarioId): array;
    public function findGlobal(array $filters = []): array;
    public function add(array $entry): array;
}
