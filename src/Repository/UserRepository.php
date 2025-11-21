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

    public function findAllDoctors(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                u.id, 
                CONCAT(u.last_name, ' ', u.first_name) as full_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = 'doctor'
            ORDER BY u.last_name, u.first_name
        ");
        return $stmt->fetchAll();
    }
}
