<?php

namespace App\Module\Appointment\Repository;

interface AppointmentRepositoryInterface
{
    public function findAll(): array;
    public function save(array $data): bool;
    public function findById(int $id): ?array;
    public function findByPatientId(int $patientId): array;
    public function update(int $id, array $data): bool;
    public function updateStatus(int $id, string $status): bool;
    // public function delete(int $id): bool;
    public function findWaitlistById(int $id): ?array;
    public function updateWaitlistStatus(int $id, string $status): bool;
}
