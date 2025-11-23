<?php

namespace App\Module\User\Repository;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?array;
    public function findById(int $id): ?array;
    public function findByEmailExcludingId(string $email, int $id): ?array;
    public function findAllDoctors(): array;
    public function findAllActive(): array;
    public function save(array $data): bool;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function countUsers(): int;
    public function ensureDefaultAdminExists(): void;
}
