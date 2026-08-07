<?php

namespace App\Bundles\LabOrderBundle\Repository;

use App\Entity\LabResource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LabResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LabResource::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, name, type, capacity, 
                is_available, notes FROM lab_resources ORDER BY name ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, name, type, capacity, is_available, 
                notes FROM lab_resources WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function save(array $data) : ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO lab_resources (name, type, capacity, is_available, notes) 
                VALUES (:name, :type, :capacity, :is_available, :notes)";

        $success = $conn->executeStatement($sql, [
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'capacity' => $data['capacity'] ?? 1,
            'is_available' => (int)($data['is_available'] ?? true),
            'notes' => $data['notes'] ?? null,
        ]) > 0;

        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE lab_resources SET name = :name, type = :type, capacity = :capacity, 
                is_available = :is_available, notes = :notes WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'capacity' => $data['capacity'] ?? 1,
            'is_available' => (int)($data['is_available'] ?? true),
            'notes' => $data['notes'] ?? null,
        ]) > 0;
    }

    // Check if a resource is available and has capacity for a given time slot
    public function checkResourceAvailability(
        int $resourceId,
        string $startTime,
        string $endTime,
        int $requiredCapacity = 1
    ) : bool {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT lr.capacity - COUNT(lor.lab_order_id) as remaining_capacity
            FROM lab_resources lr
            LEFT JOIN lab_order_resources lor ON lr.id = lor.lab_resource_id
            LEFT JOIN lab_orders lo ON lor.lab_order_id = lo.id
            WHERE lr.id = :resource_id
              AND lr.is_available = TRUE
              AND (lo.id IS NULL OR (lo.start_time NOT BETWEEN :start_time AND :end_time 
              AND lo.end_time NOT BETWEEN :start_time AND :end_time))
            GROUP BY lr.id, lr.capacity
        ";

        $result = $conn->fetchAssociative($sql, [
            'resource_id' => $resourceId,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return ($result && $result['remaining_capacity'] >= $requiredCapacity);
    }
}
