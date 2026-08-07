<?php

namespace App\Bundles\AdminBundle\Repository;

use App\Entity\BackupPolicy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BackupPolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackupPolicy::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM backup_policies ORDER BY name ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM backup_policies WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function save(array $data) : ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO backup_policies (name, description, frequency, retention_days, status) 
                VALUES (:name, :description, :frequency, :retention_days, :status)";

        $success = $conn->executeStatement($sql, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'frequency' => $data['frequency'] ?? 'daily',
            'retention_days' => $data['retention_days'] ?? 30,
            'status' => $data['status'] ?? 'inactive',
        ]) > 0;

        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE backup_policies SET 
                    name = :name, 
                    description = :description, 
                    frequency = :frequency, 
                    retention_days = :retention_days, 
                    last_run_at = :last_run_at, 
                    status = :status 
                WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'frequency' => $data['frequency'] ?? 'daily',
            'retention_days' => $data['retention_days'] ?? 30,
            'last_run_at' => $data['last_run_at'] ?? null,
            'status' => $data['status'] ?? 'inactive',
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM backup_policies WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
