<?php
namespace App\Repositories;

interface TaskRepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;
    public function findByResponsable(string $responsableId): array;
    public function findByAsignador(string $asignadorId): array;
    public function findByOffice(string $oficinaId): array;
    public function findByDepartment(string $departamentoId): array;
    public function findByEstado(string $estado): array;
    public function save(array $task): array;
    public function delete(string $id): bool;
    public function nextFolio(): string;
}
