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

    public function save(array $data): bool
    {
        $sql = "INSERT INTO medical_records (patient_id, appointment_id, doctor_id, visit_date, diagnosis_code, diagnosis_text, treatment, notes) 
                VALUES (:patient_id, :appointment_id, :doctor_id, :visit_date, :diagnosis_code, :diagnosis_text, :treatment, :notes)";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':appointment_id' => $data['appointment_id'],
            ':doctor_id' => $data['doctor_id'],
            ':visit_date' => $data['visit_date'],
            ':diagnosis_code' => $data['diagnosis_code'] ?? null,
            ':diagnosis_text' => $data['diagnosis_text'] ?? null,
            ':treatment' => $data['treatment'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
    }
}
