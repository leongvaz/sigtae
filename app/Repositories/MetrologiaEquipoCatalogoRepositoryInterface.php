<?php
namespace App\Repositories;

interface MetrologiaEquipoCatalogoRepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;

    /**
     * @param array<string, mixed> $filters
     */
    public function findByFilters(array $filters = []): array;

    /**
     * @param array<string, mixed> $entity
     * @return array<string, mixed>
     */
    public function save(array $entity): array;

    public function delete(string $id): bool;

    public function findSuggestions(string $field, string $q, int $limit = 12): array;
}

