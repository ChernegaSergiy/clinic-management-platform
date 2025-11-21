<?php

namespace App\Repository;

use App\Database;
use PDO;

class MedicalRecordRepository implements MedicalRecordRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                mr.*,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM medical_records mr
            JOIN users u ON mr.doctor_id = u.id
            WHERE mr.patient_id = :patient_id
            ORDER BY mr.visit_date DESC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll();
    }
}
