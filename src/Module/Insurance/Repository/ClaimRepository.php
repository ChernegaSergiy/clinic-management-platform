<?php

declare(strict_types=1);

namespace App\Module\Insurance\Repository;

use App\Entity\Claim;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Claim::class);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM claims WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function findByIdWithDetails(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
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
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function findByInvoiceId(int $invoiceId) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM claims WHERE invoice_id = :invoice_id";
        $result = $conn->fetchAssociative($sql, ['invoice_id' => $invoiceId]);
        return $result ?: null;
    }

    public function findByPatientPolicyId(int $patientPolicyId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM claims WHERE patient_policy_id = :patient_policy_id";
        return $conn->fetchAllAssociative($sql, ['patient_policy_id' => $patientPolicyId]);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
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
        return $conn->fetchAllAssociative($sql);
    }

    public function create(int $invoiceId, int $patientPolicyId, string $status, float $totalClaimed, ?string $submittedAt = null) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO claims (invoice_id, patient_policy_id, status, submitted_at, total_claimed, created_at, updated_at)
            VALUES (:invoice_id, :patient_policy_id, :status, :submitted_at, :total_claimed, NOW(), NOW())
        ";
        $conn->executeStatement($sql, [
            'invoice_id' => $invoiceId,
            'patient_policy_id' => $patientPolicyId,
            'status' => $status,
            'submitted_at' => $submittedAt,
            'total_claimed' => $totalClaimed,
        ]);
        return (int) $conn->lastInsertId();
    }

    public function update(int $id, string $status, ?string $submittedAt = null, ?float $totalPaid = null) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            UPDATE claims
            SET status = :status, submitted_at = :submitted_at, total_paid = :total_paid, updated_at = NOW()
            WHERE id = :id
        ";
        return $conn->executeStatement($sql, [
            'id' => $id,
            'status' => $status,
            'submitted_at' => $submittedAt,
            'total_paid' => $totalPaid,
        ]) > 0;
    }
}
