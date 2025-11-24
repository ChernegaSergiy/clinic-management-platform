<?php

namespace App\Module\Patient\Repository;

interface PatientRepositoryInterface
{
    public function findAll(string $searchTerm = ''): array;
    public function save(array $data): bool;
    public function findByCredentials(string $lastName, string $firstName, string $birthDate): ?array;
    public function findByTaxId(string $taxId, ?int $excludeId = null): ?array;
    public function findById(int $id): ?array;
    public function update(int $id, array $data): bool;
    public function updateStatus(int $id, string $status): bool;
    public function findAllActive(): array;
    public function getLastError(): ?string;
    // public function delete(int $id): bool;
}
