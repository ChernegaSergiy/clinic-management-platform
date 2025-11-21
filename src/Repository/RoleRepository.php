<?php

namespace App\Repository;

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
}
