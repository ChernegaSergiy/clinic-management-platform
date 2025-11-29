<?php

declare(strict_types=1);

namespace App\Module\Insurance\Repository;

use App\Database;

class ClaimRepository
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->database->getConnection()->prepare("SELECT * FROM claims WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByInvoiceId(int $invoiceId): ?array
    {
        $stmt = $this->database->getConnection()->prepare("SELECT * FROM claims WHERE invoice_id = :invoice_id");
        $stmt->bindParam(':invoice_id', $invoiceId);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByPatientPolicyId(int $patientPolicyId): array
    {
        $stmt = $this->database->getConnection()->prepare("SELECT * FROM claims WHERE patient_policy_id = :patient_policy_id");
        $stmt->bindParam(':patient_policy_id', $patientPolicyId);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        $stmt = $this->database->getConnection()->prepare("SELECT * FROM claims ORDER BY submitted_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(int $invoiceId, int $patientPolicyId, string $status, float $totalClaimed, ?string $submittedAt = null): int
    {
        $stmt = $this->database->getConnection()->prepare("
            INSERT INTO claims (invoice_id, patient_policy_id, status, submitted_at, total_claimed, created_at, updated_at)
            VALUES (:invoice_id, :patient_policy_id, :status, :submitted_at, :total_claimed, NOW(), NOW())
        ");
        $stmt->bindParam(':invoice_id', $invoiceId);
        $stmt->bindParam(':patient_policy_id', $patientPolicyId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':submitted_at', $submittedAt);
        $stmt->bindParam(':total_claimed', $totalClaimed);
        $stmt->execute();
        return (int) $this->database->getConnection()->lastInsertId();
    }

    public function update(int $id, string $status, ?string $submittedAt = null, ?float $totalPaid = null): bool
    {
        $stmt = $this->database->getConnection()->prepare("
            UPDATE claims
            SET status = :status, submitted_at = :submitted_at, total_paid = :total_paid, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':submitted_at', $submittedAt);
        $stmt->bindParam(':total_paid', $totalPaid);
        return $stmt->execute();
    }
}
