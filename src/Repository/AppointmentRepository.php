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
                a.status,
                a.doctor_id
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            ORDER BY a.start_time DESC
        ");
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO appointments (patient_id, doctor_id, start_time, end_time, status, notes) 
                VALUES (:patient_id, :doctor_id, :start_time, :end_time, :status, :notes)";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':doctor_id' => $data['doctor_id'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':status' => $data['status'] ?? 'scheduled', // Встановлюємо статус за замовчуванням
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.*, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            WHERE a.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE appointments SET 
                    patient_id = :patient_id, 
                    doctor_id = :doctor_id, 
                    start_time = :start_time, 
                    end_time = :end_time, 
                    status = :status, 
                    notes = :notes 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':patient_id' => $data['patient_id'],
            ':doctor_id' => $data['doctor_id'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':status' => $data['status'],
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE appointments SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.*, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.doctor_id = u.id
            WHERE a.patient_id = :patient_id
            ORDER BY a.start_time DESC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function findByDateRange(string $start, string $end): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                a.start_time, 
                a.end_time, 
                a.status,
                a.doctor_id
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            WHERE a.start_time >= :start_time AND a.end_time <= :end_time
            ORDER BY a.start_time ASC
        ");
        $stmt->execute([
            ':start_time' => $start,
            ':end_time' => $end,
        ]);
        return $stmt->fetchAll();
    }
}
