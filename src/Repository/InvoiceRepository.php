<?php

namespace App\Repository;

use App\Database;
use PDO;

class InvoiceRepository implements InvoiceRepositoryInterface
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
                i.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                i.amount,
                i.status,
                i.issued_date
            FROM invoices i
            JOIN patients p ON i.patient_id = p.id
            ORDER BY i.issued_date DESC
        ");
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO invoices (patient_id, appointment_id, medical_record_id, amount, status, notes) 
                VALUES (:patient_id, :appointment_id, :medical_record_id, :amount, :status, :notes)";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':appointment_id' => $data['appointment_id'] ?? null,
            ':medical_record_id' => $data['medical_record_id'] ?? null,
            ':amount' => $data['amount'],
            ':status' => $data['status'] ?? 'pending',
            ':notes' => $data['notes'] ?? null,
        ]);
    }
}
