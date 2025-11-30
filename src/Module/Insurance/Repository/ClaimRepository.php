<?php

declare(strict_types=1);

namespace App\Module\Insurance\Repository;

use App\Database;

class ClaimRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM claims WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByIdWithDetails(int $id): ?array
    {
        $sql = "
            SELECT 
                c.*,
                pip.patient_id,
                p.first_name,
                p.last_name,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                ic.name as insurance_company_name
            FROM claims c
            JOIN patient_insurance_policies pip ON c.patient_policy_id = pip.id
            JOIN patients p ON pip.patient_id = p.id
            JOIN insurance_companies ic ON pip.insurance_company_id = ic.id
            WHERE c.id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByInvoiceId(int $invoiceId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM claims WHERE invoice_id = :invoice_id");
        $stmt->bindParam(':invoice_id', $invoiceId);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByPatientPolicyId(int $patientPolicyId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM claims WHERE patient_policy_id = :patient_policy_id");
        $stmt->bindParam(':patient_policy_id', $patientPolicyId);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        $sql = "
            SELECT 
                c.*,
                pip.patient_id,
                p.first_name,
                p.last_name,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                ic.name as insurance_company_name
            FROM claims c
            JOIN patient_insurance_policies pip ON c.patient_policy_id = pip.id
            JOIN patients p ON pip.patient_id = p.id
            JOIN insurance_companies ic ON pip.insurance_company_id = ic.id
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(int $invoiceId, int $patientPolicyId, string $status, float $totalClaimed, ?string $submittedAt = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO claims (invoice_id, patient_policy_id, status, submitted_at, total_claimed, created_at, updated_at)
            VALUES (:invoice_id, :patient_policy_id, :status, :submitted_at, :total_claimed, NOW(), NOW())
        ");
        $stmt->bindParam(':invoice_id', $invoiceId);
        $stmt->bindParam(':patient_policy_id', $patientPolicyId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':submitted_at', $submittedAt);
        $stmt->bindParam(':total_claimed', $totalClaimed);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $status, ?string $submittedAt = null, ?float $totalPaid = null): bool
    {
        $stmt = $this->pdo->prepare("
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
