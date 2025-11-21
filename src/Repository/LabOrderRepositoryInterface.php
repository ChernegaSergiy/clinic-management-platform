<?php

namespace App\Repository;

interface LabOrderRepositoryInterface
{
    public function findByMedicalRecordId(int $medicalRecordId): array;
    public function save(array $data): bool;
    public function findById(int $id): ?array;
    // public function update(int $id, array $data): bool;
    // public function delete(int $id): bool;
}
