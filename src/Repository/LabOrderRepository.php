<?php

namespace App\Repository;

use App\Database;
use PDO;

class LabOrderRepository implements LabOrderRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByMedicalRecordId(int $medicalRecordId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM lab_orders
            WHERE medical_record_id = :medical_record_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':medical_record_id' => $medicalRecordId]);
        return $stmt->fetchAll();
    }
}
