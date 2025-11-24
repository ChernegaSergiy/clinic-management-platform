<?php

namespace App\Module\MedicalRecord\Repository;

interface MedicalRecordRepositoryInterface
{
    public function findByPatientId(int $patientId): array;
    public function findAll(): array;
    public function save(array $data): int|false;
    public function findById(int $id): ?array;
    // public function update(int $id, array $data): bool;
    // public function delete(int $id): bool;
}
