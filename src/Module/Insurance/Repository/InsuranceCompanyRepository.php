<?php

declare(strict_types=1);

namespace App\Module\Insurance\Repository;

use App\Entity\InsuranceCompany;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InsuranceCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InsuranceCompany::class);
    }

    public function findById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM insurance_companies WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function findAll(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM insurance_companies";
        return $conn->fetchAllAssociative($sql);
    }

    public function create(string $name, ?string $contactPerson = null, ?string $phone = null, ?string $email = null, ?string $notes = null): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            INSERT INTO insurance_companies (name, contact_person, phone, email, notes, created_at, updated_at)
            VALUES (:name, :contact_person, :phone, :email, :notes, NOW(), NOW())
        ";
        $conn->executeStatement($sql, [
            'name' => $name,
            'contact_person' => $contactPerson,
            'phone' => $phone,
            'email' => $email,
            'notes' => $notes,
        ]);
        return (int) $conn->lastInsertId();
    }

    public function update(int $id, string $name, ?string $contactPerson = null, ?string $phone = null, ?string $email = null, ?string $notes = null): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            UPDATE insurance_companies
            SET name = :name, contact_person = :contact_person, phone = :phone, email = :email, notes = :notes, updated_at = NOW()
            WHERE id = :id
        ";
        return $conn->executeStatement($sql, [
            'id' => $id,
            'name' => $name,
            'contact_person' => $contactPerson,
            'phone' => $phone,
            'email' => $email,
            'notes' => $notes,
        ]) > 0;
    }

    public function delete(int $id): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM insurance_companies WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
