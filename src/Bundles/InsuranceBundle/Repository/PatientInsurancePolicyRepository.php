<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

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
