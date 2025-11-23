<?php

namespace App\Module\Admin\Repository;

use App\Database;
use PDO;

class DictionaryRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // --- Dictionary Definitions ---
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM dictionaries ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dictionaries WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function save(array $data): ?int
    {
        $sql = "INSERT INTO dictionaries (name, description, type) VALUES (:name, :description, :type)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'] ?? null,
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE dictionaries SET name = :name, description = :description, type = :type WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM dictionaries WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // --- Dictionary Values ---
    public function findValuesByDictionaryId(int $dictionaryId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dictionary_values WHERE dictionary_id = :dictionary_id ORDER BY order_num ASC, label ASC");
        $stmt->execute([':dictionary_id' => $dictionaryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findValueById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dictionary_values WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function saveValue(array $data): ?int
    {
        $sql = "INSERT INTO dictionary_values (dictionary_id, value, label, order_num, is_active) 
                VALUES (:dictionary_id, :value, :label, :order_num, :is_active)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':dictionary_id' => $data['dictionary_id'],
            ':value' => $data['value'],
            ':label' => $data['label'],
            ':order_num' => $data['order_num'] ?? 0,
            ':is_active' => $data['is_active'] ?? true,
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function updateValue(int $id, array $data): bool
    {
        $sql = "UPDATE dictionary_values SET 
                    dictionary_id = :dictionary_id, 
                    value = :value, 
                    label = :label, 
                    order_num = :order_num, 
                    is_active = :is_active 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':dictionary_id' => $data['dictionary_id'],
            ':value' => $data['value'],
            ':label' => $data['label'],
            ':order_num' => $data['order_num'] ?? 0,
            ':is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function deleteValue(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM dictionary_values WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
