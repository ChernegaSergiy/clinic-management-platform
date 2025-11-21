<?php

namespace App\Repository;

use App\Database;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, first_name, last_name, email, role_id FROM users ORDER BY last_name, first_name");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email, role_id FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO users (first_name, last_name, email, password, role_id) 
                VALUES (:first_name, :last_name, :email, :password, :role_id)";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role_id' => $data['role_id'],
        ]);
    }
}
