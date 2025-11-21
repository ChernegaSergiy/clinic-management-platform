<?php

namespace App\Repository;

use App\Database;
use PDO;

class ServiceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT s.*, sc.name as category_name 
            FROM services s
            LEFT JOIN service_categories sc ON s.category_id = sc.id
            ORDER BY s.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, sc.name as category_name 
            FROM services s
            LEFT JOIN service_categories sc ON s.category_id = sc.id
            WHERE s.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function save(array $data): ?int
    {
        $sql = "INSERT INTO services (name, description, price, category_id, is_active) 
                VALUES (:name, :description, :price, :category_id, :is_active)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':category_id' => $data['category_id'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE services SET 
                    name = :name, 
                    description = :description, 
                    price = :price, 
                    category_id = :category_id, 
                    is_active = :is_active 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':category_id' => $data['category_id'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function findCategories(): array
    {
        $stmt = $this->pdo->query("SELECT id, name FROM service_categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveCategory(array $data): ?int
    {
        $sql = "INSERT INTO service_categories (name, description) VALUES (:name, :description)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }
}