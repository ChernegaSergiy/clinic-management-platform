<?php

namespace App\Module\Admin\Repository;

use App\Database;
use PDO;

class AuthConfigRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM auth_configs ORDER BY provider ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActive(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM auth_configs WHERE is_active = 1 ORDER BY provider ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM auth_configs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function findByProvider(string $provider): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM auth_configs WHERE provider = :provider");
        $stmt->execute([':provider' => $provider]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function save(array $data): ?int
    {
        $sql = "INSERT INTO auth_configs (provider, client_id, client_secret, is_active, config) 
                VALUES (:provider, :client_id, :client_secret, :is_active, :config)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':provider' => $data['provider'],
            ':client_id' => $data['client_id'] ?? null,
            ':client_secret' => $data['client_secret'] ?? null,
            ':is_active' => $data['is_active'] ?? false,
            ':config' => $data['config'] ?? null,
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE auth_configs SET 
                    provider = :provider, 
                    client_id = :client_id, 
                    client_secret = :client_secret, 
                    is_active = :is_active, 
                    config = :config 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':provider' => $data['provider'],
            ':client_id' => $data['client_id'] ?? null,
            ':client_secret' => $data['client_secret'] ?? null,
            ':is_active' => $data['is_active'] ?? false,
            ':config' => $data['config'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM auth_configs WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
