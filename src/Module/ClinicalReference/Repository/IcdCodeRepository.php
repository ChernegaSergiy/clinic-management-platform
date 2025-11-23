<?php

namespace App\Module\ClinicalReference\Repository;

use App\Database;
use PDO;

class IcdCodeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, code, description FROM icd_codes ORDER BY code ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByCodeOrDescription(string $searchTerm): array
    {
        $sql = "SELECT id, code, description FROM icd_codes WHERE code LIKE :term OR description LIKE :term ORDER BY code ASC LIMIT 20";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':term' => '%' . $searchTerm . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
