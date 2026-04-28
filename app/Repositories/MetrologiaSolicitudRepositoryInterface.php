<?php
namespace App\Repositories;

interface MetrologiaSolicitudRepositoryInterface extends RepositoryInterface
{
    public function findByFilters(array $filters = []): array;
    public function existsFolio(string $folio, ?string $excludeId = null): bool;
    public function nextFolio(int $year): string;
}

