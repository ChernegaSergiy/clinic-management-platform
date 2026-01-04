<?php

declare(strict_types=1);

namespace App\Module\Insurance\Repository;

use Api\Database\Database;

class PatientInsurancePolicyRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM patient_insurance_policies WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM patient_insurance_policies WHERE patient_id = :patient_id ORDER BY is_active DESC, valid_to DESC");
        $stmt->bindParam(':patient_id', $patientId);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(int $patientId, int $insuranceCompanyId, string $policyNumber, ?string $groupNumber, string $validFrom, ?string $validTo, bool $isActive): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO patient_insurance_policies (patient_id, insurance_company_id, policy_number, group_number, valid_from, valid_to, is_active, created_at, updated_at)
            VALUES (:patient_id, :insurance_company_id, :policy_number, :group_number, :valid_from, :valid_to, :is_active, NOW(), NOW())
        ");
        $stmt->bindParam(':patient_id', $patientId);
        $stmt->bindParam(':insurance_company_id', $insuranceCompanyId);
        $stmt->bindParam(':policy_number', $policyNumber);
        $stmt->bindParam(':group_number', $groupNumber);
        $stmt->bindParam(':valid_from', $validFrom);
        $stmt->bindParam(':valid_to', $validTo);
        $stmt->bindValue(':is_active', (int) $isActive, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $patientId, int $insuranceCompanyId, string $policyNumber, ?string $groupNumber, string $validFrom, ?string $validTo, bool $isActive): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE patient_insurance_policies
            SET patient_id = :patient_id, insurance_company_id = :insurance_company_id, policy_number = :policy_number, group_number = :group_number, valid_from = :valid_from, valid_to = :valid_to, is_active = :is_active, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':patient_id', $patientId);
        $stmt->bindParam(':insurance_company_id', $insuranceCompanyId);
        $stmt->bindParam(':policy_number', $policyNumber);
        $stmt->bindParam(':group_number', $groupNumber);
        $stmt->bindParam(':valid_from', $validFrom);
        $stmt->bindParam(':valid_to', $validTo);
        $stmt->bindValue(':is_active', (int) $isActive, \PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM patient_insurance_policies WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
