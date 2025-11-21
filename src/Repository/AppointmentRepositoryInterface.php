<?php

namespace App\Repository;

interface AppointmentRepositoryInterface
{
    public function findAll(): array;
    public function save(array $data): bool;
    public function findById(int $id): ?array;
    public function update(int $id, array $data): bool;
    public function updateStatus(int $id, string $status): bool;
    // public function delete(int $id): bool;
}
