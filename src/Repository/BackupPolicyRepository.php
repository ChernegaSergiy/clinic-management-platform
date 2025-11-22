<?php

namespace App\Repository;

use App\Database;
use PDO;

class BackupPolicyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM backup_policies ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM backup_policies WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function save(array $data): ?int
    {
        $sql = "INSERT INTO backup_policies (name, description, frequency, retention_days, status) 
                VALUES (:name, :description, :frequency, :retention_days, :status)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':frequency' => $data['frequency'] ?? 'daily',
            ':retention_days' => $data['retention_days'] ?? 30,
            ':status' => $data['status'] ?? 'inactive',
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE backup_policies SET 
                    name = :name, 
                    description = :description, 
                    frequency = :frequency, 
                    retention_days = :retention_days, 
                    last_run_at = :last_run_at, 
                    status = :status 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':frequency' => $data['frequency'] ?? 'daily',
            ':retention_days' => $data['retention_days'] ?? 30,
            ':last_run_at' => $data['last_run_at'] ?? null,
            ':status' => $data['status'] ?? 'inactive',
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM backup_policies WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}