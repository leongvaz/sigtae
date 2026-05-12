<?php
namespace App\Repositories;

interface MetrologiaRecepcionRepositoryInterface
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

    public function existsFolioRecepcion(string $folioRecepcion, ?string $excludeId = null): bool;

    public function nextFolioRecepcion(int $year): string;
}

