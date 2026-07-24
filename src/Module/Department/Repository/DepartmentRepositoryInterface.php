<?php

namespace App\Module\Department\Repository;

interface DepartmentRepositoryInterface
{
    public function findAll(): array;
    public function findAllActive(): array;
    public function findById(int $id): ?array;
    public function save(array $data): bool;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function findByName(string $name): ?array;
    public function getHierarchy(): array;
}
