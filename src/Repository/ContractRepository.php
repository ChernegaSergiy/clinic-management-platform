<?php

namespace App\Repository;

use App\Database;
use PDO;

class ContractRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM contracts ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contracts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function save(array $data): ?int
    {
        $sql = "INSERT INTO contracts (title, description, start_date, end_date, party_a, party_b, file_path, status) 
                VALUES (:title, :description, :start_date, :end_date, :party_a, :party_b, :file_path, :status)";
        
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'] ?? null,
            ':party_a' => $data['party_a'] ?? null,
            ':party_b' => $data['party_b'] ?? null,
            ':file_path' => $data['file_path'] ?? null,
            ':status' => $data['status'] ?? 'active',
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE contracts SET 
                    title = :title, 
                    description = :description, 
                    start_date = :start_date, 
                    end_date = :end_date, 
                    party_a = :party_a, 
                    party_b = :party_b, 
                    file_path = :file_path, 
                    status = :status 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'] ?? null,
            ':party_a' => $data['party_a'] ?? null,
            ':party_b' => $data['party_b'] ?? null,
            ':file_path' => $data['file_path'] ?? null,
            ':status' => $data['status'] ?? 'active',
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM contracts WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}