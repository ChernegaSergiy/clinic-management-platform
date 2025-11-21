<?php

namespace App\Repository;

interface AppointmentRepositoryInterface
{
    public function findAll(): array;
    public function save(array $data): bool;
    // public function findById(int $id): ?array;
    // public function delete(int $id): bool;
}
