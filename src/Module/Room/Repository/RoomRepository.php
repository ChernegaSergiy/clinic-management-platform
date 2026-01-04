<?php

namespace App\Module\Room\Repository;

use Api\Database\Database;
use PDO;

class RoomRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM rooms ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM rooms WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findAvailable(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM rooms WHERE is_available = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO rooms (name, type, capacity, location, equipment, is_available) 
                  VALUES (:name, :type, :capacity, :location, :equipment, :is_available)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':capacity' => (int)$data['capacity'],
            ':location' => $data['location'] ?? null,
            ':equipment' => $data['equipment'] ?? null,
            ':is_available' => $data['is_available'] ?? true,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $setParts = [];
        $params = [':id' => $id];

        $allowedFields = ['name', 'type', 'capacity', 'location', 'equipment', 'is_available'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $setParts[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($setParts)) {
            return true;
        }

        $sql = "UPDATE rooms SET " . implode(', ', $setParts) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM rooms WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
