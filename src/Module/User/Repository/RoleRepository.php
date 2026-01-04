<?php

namespace App\Module\User\Repository;

use App\Database;
use PDO;

class RoleRepository implements RoleRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, name FROM roles ORDER BY name");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name FROM roles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO roles (name, description) VALUES (:name, :description)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE roles SET name = :name, description = :description WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM roles WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
