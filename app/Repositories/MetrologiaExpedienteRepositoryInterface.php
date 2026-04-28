<?php
namespace App\Repositories;

interface MetrologiaExpedienteRepositoryInterface extends RepositoryInterface
{
    public function findByFilters(array $filters = []): array;
    public function existsFolio(string $folio, ?string $excludeId = null): bool;
    public function nextFolio(int $year): string;
    public function updateFolio(string $id, string $newFolio, array $meta = []): array;
}

