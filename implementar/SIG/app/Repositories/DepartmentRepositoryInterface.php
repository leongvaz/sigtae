<?php
namespace App\Repositories;

interface DepartmentRepositoryInterface
{
    public function find(string $id): ?array;
    public function findAll(): array;
}
