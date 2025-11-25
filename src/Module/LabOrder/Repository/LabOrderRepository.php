<?php

namespace App\Module\LabOrder\Repository;

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

    public function save(array $data): int|false
    {
        $sql = "INSERT INTO lab_orders (patient_id, doctor_id, medical_record_id, 
                                        order_code, qr_code_hash, results, status) 
                VALUES (:patient_id, :doctor_id, :medical_record_id, 
                        :order_code, :qr_code_hash, :results, :status)";

        $stmt = $this->pdo->prepare($sql);

        $success = $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':doctor_id' => $data['doctor_id'],
            ':medical_record_id' => $data['medical_record_id'],
            ':order_code' => $data['order_code'],
            ':qr_code_hash' => $data['qr_code_hash'],
            ':results' => $data['results'] ?? null,
            ':status' => $data['status'] ?? 'ordered',
        ]);

        if ($success) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                lo.*,
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM lab_orders lo
            JOIN patients p ON lo.patient_id = p.id
            JOIN users u ON lo.doctor_id = u.id
            WHERE lo.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE lab_orders SET 
                    order_code = :order_code, 
                    status = :status, 
                    results = :results, 
                    notes = :notes 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':order_code' => $data['order_code'],
            ':status' => $data['status'],
            ':results' => $data['results'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
    }
}
