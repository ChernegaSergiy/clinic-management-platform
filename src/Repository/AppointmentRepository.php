<?php

namespace App\Repository;

use App\Database;
use PDO;

class AppointmentRepository implements AppointmentRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                a.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                a.start_time, 
                a.end_time, 
                a.status
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            ORDER BY a.start_time DESC
        ");
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO appointments (patient_id, doctor_id, start_time, end_time, notes) 
                VALUES (:patient_id, :doctor_id, :start_time, :end_time, :notes)";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':doctor_id' => $data['doctor_id'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':notes' => $data['notes'] ?? null,
        ]);
    }
}
