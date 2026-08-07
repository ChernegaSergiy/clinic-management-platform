<?php

declare(strict_types=1);

namespace App\Bundles\InsuranceBundle\Repository;

use App\Entity\PatientInsurancePolicy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PatientInsurancePolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientInsurancePolicy::class);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM patient_insurance_policies WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function findByPatientId(int $patientId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM patient_insurance_policies WHERE patient_id = :patient_id ORDER BY is_active DESC, valid_to DESC";
        return $conn->fetchAllAssociative($sql, ['patient_id' => $patientId]);
    }

    public function create(int $patientId, int $insuranceCompanyId, string $policyNumber, ?string $groupNumber, string $validFrom, ?string $validTo, bool $isActive) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO patient_insurance_policies (patient_id, insurance_company_id, policy_number, group_number, valid_from, valid_to, is_active, created_at, updated_at)
            VALUES (:patient_id, :insurance_company_id, :policy_number, :group_number, :valid_from, :valid_to, :is_active, NOW(), NOW())
        ";
        $conn->executeStatement($sql, [
            'patient_id' => $patientId,
            'insurance_company_id' => $insuranceCompanyId,
            'policy_number' => $policyNumber,
            'group_number' => $groupNumber,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'is_active' => (int) $isActive,
        ]);
        return (int) $conn->lastInsertId();
    }

    public function update(int $id, int $patientId, int $insuranceCompanyId, string $policyNumber, ?string $groupNumber, string $validFrom, ?string $validTo, bool $isActive) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            UPDATE patient_insurance_policies
            SET patient_id = :patient_id, insurance_company_id = :insurance_company_id, policy_number = :policy_number, group_number = :group_number, valid_from = :valid_from, valid_to = :valid_to, is_active = :is_active, updated_at = NOW()
            WHERE id = :id
        ";
        return $conn->executeStatement($sql, [
            'id' => $id,
            'patient_id' => $patientId,
            'insurance_company_id' => $insuranceCompanyId,
            'policy_number' => $policyNumber,
            'group_number' => $groupNumber,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'is_active' => (int) $isActive,
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM patient_insurance_policies WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
