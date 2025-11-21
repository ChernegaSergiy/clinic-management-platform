<?php

namespace App\Repository;

use App\Database;
use PDO;

class LabResourceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, type, capacity, is_available, notes FROM lab_resources ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, type, capacity, is_available, notes FROM lab_resources WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function save(array $data): ?int
    {
        $sql = "INSERT INTO lab_resources (name, type, capacity, is_available, notes) VALUES (:name, :type, :capacity, :is_available, :notes)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':name' => $data['name'],
            ':type' => $data['type'] ?? null,
            ':capacity' => $data['capacity'] ?? 1,
            ':is_available' => $data['is_available'] ?? true,
            ':notes' => $data['notes'] ?? null,
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE lab_resources SET name = :name, type = :type, capacity = :capacity, is_available = :is_available, notes = :notes WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':type' => $data['type'] ?? null,
            ':capacity' => $data['capacity'] ?? 1,
            ':is_available' => $data['is_available'] ?? true,
            ':notes' => $data['notes'] ?? null,
        ]);
    }
    
    // Check if a resource is available and has capacity for a given time slot
    public function checkResourceAvailability(int $resourceId, string $startTime, string $endTime, int $requiredCapacity = 1): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT lr.capacity - COUNT(lor.lab_order_id) as remaining_capacity
            FROM lab_resources lr
            LEFT JOIN lab_order_resources lor ON lr.id = lor.lab_resource_id
            LEFT JOIN lab_orders lo ON lor.lab_order_id = lo.id
            WHERE lr.id = :resource_id
              AND lr.is_available = TRUE
              AND (lo.id IS NULL OR (lo.start_time NOT BETWEEN :start_time AND :end_time AND lo.end_time NOT BETWEEN :start_time AND :end_time))
            GROUP BY lr.id, lr.capacity
        ");
        $stmt->execute([
            ':resource_id' => $resourceId,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result && $result['remaining_capacity'] >= $requiredCapacity);
    }
}