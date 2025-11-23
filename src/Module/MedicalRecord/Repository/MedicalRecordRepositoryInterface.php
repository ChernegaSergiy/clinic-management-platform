<?php

namespace App\Module\MedicalRecord\Repository;

interface MedicalRecordRepositoryInterface
{
    public function findByPatientId(int $patientId): array;
    public function save(array $data): bool;
    public function findById(int $id): ?array;
    // public function update(int $id, array $data): bool;
    // public function delete(int $id): bool;
}
