<?php

namespace App\Module\Billing\Repository;

interface InvoiceRepositoryInterface
{
    public function findAll(string $searchTerm = ''): array;
    public function save(array $data): bool;
    public function findById(int $id): ?array;
    public function update(int $id, array $data): bool;
    public function logFinancialTransaction(int $patientId, float $amount, string $transactionType, string $description, ?int $entityId = null): bool;
    // public function delete(int $id): bool;
}
