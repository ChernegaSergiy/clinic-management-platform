<?php

namespace App\Module\Department\Repository;

use App\Database\Database;
use PDO;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $sql = "
            SELECT d.*, dp.name as parent_name 
            FROM departments d 
            LEFT JOIN departments dp ON d.parent_id = dp.id 
            ORDER BY d.sort_order ASC, d.name ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findAllActive(): array
    {
        $sql = "
            SELECT d.*, dp.name as parent_name 
            FROM departments d 
            LEFT JOIN departments dp ON d.parent_id = dp.id 
            WHERE d.is_active = 1 
            ORDER BY d.sort_order ASC, d.name ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT d.*, dp.name as parent_name 
            FROM departments d 
            LEFT JOIN departments dp ON d.parent_id = dp.id 
            WHERE d.id = :id
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function save(array $data): bool
    {
        $sql = "
            INSERT INTO departments (name, description, parent_id, is_active, sort_order) 
            VALUES (:name, :description, :parent_id, :is_active, :sort_order)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
            ':sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE departments SET
                name = :name,
                description = :description,
                parent_id = :parent_id,
                is_active = :is_active,
                sort_order = :sort_order
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
            ':sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function delete(int $id): bool
    {
        // Перевірка чи немає дочірніх відділень
        $checkSql = "SELECT COUNT(*) as count FROM departments WHERE parent_id = :id";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([':id' => $id]);
        $hasChildren = $checkStmt->fetch()['count'] > 0;

        if ($hasChildren) {
            return false;
        }

        $sql = "DELETE FROM departments WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM departments WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function getHierarchy(): array
    {
        $sql = "
            SELECT d.*, dp.name as parent_name 
            FROM departments d 
            LEFT JOIN departments dp ON d.parent_id = dp.id 
            WHERE d.is_active = 1 
            ORDER BY d.sort_order ASC, d.name ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $departments = $stmt->fetchAll();

        // Побудова ієрархії
        $hierarchy = [];
        $parentMap = [];

        // Спочатку групуємо за parent_id
        foreach ($departments as $department) {
            $parentId = $department['parent_id'];
            if ($parentId === null) {
                $hierarchy[] = $department;
            } else {
                $parentMap[$parentId][] = $department;
            }
        }

        // Потім додаємо дочірні елементи
        foreach ($hierarchy as &$parent) {
            $this->addChildren($parent, $parentMap);
        }

        return $hierarchy;
    }

    private function addChildren(array &$parent, array $parentMap): void
    {
        $parentId = $parent['id'];
        if (isset($parentMap[$parentId])) {
            $parent['children'] = $parentMap[$parentId];
            foreach ($parent['children'] as &$child) {
                $this->addChildren($child, $parentMap);
            }
        }
    }
}