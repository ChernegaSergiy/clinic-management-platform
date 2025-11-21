<?php

namespace App\Repository;

use App\Database;
use PDO;

class PatientRepository implements PatientRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, CONCAT(last_name, ' ', first_name) as name, birth_date, phone FROM patients ORDER BY last_name, first_name");
        return $stmt->fetchAll();
    }
}
