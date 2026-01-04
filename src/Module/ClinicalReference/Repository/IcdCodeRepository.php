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

    public function countAll(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM icd_codes")->fetchColumn();
    }

    public function replaceAll(array $rows): int
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec("DELETE FROM icd_codes");
            $stmt = $this->pdo->prepare("INSERT INTO icd_codes (code, description) VALUES (:code, :description)");
            $count = 0;
            $seen = [];
            foreach ($rows as $row) {
                $code = trim($row['code'] ?? '');
                if ($code === '' || $code === '-') {
                    continue; // пропускаємо пусті/технічні коди
                }
                if (isset($seen[$code])) {
                    continue; // уникаємо дублювання
                }
                $description = $row['description'] ?? '';
                $stmt->execute([
                    ':code' => $code,
                    ':description' => $description,
                ]);
                $seen[$code] = true;
                $count++;
            }
            $this->pdo->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function searchByCodeOrDescription(string $searchTerm): array
    {
        $sql = "SELECT id, code, description FROM icd_codes 
                WHERE code LIKE :term OR description LIKE :term 
                ORDER BY code ASC LIMIT 20";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':term' => '%' . $searchTerm . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
