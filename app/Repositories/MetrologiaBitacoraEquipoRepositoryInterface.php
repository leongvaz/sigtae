<?php
namespace App\Repositories;

interface MetrologiaBitacoraEquipoRepositoryInterface
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

    public function existsFolio(string $folio, ?string $excludeId = null): bool;

    public function findBySerie(string $serie): array;
}

