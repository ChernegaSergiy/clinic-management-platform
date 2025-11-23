<?php

namespace App\Module\User\Repository;

interface RoleRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?array;
    // public function save(array $data): bool;
    // public function update(int $id, array $data): bool;
    // public function delete(int $id): bool;
}
