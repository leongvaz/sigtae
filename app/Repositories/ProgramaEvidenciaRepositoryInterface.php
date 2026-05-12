<?php
namespace App\Repositories;

interface ProgramaEvidenciaRepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;
    public function findByPrograma(string $programaId): array;
    public function findByActividadFecha(string $actividadId, string $fechaColumna): array;
    public function save(array $entity): array;
    public function delete(string $id): bool;
}

