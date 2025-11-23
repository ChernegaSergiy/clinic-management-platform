<?php

namespace App\Module\Prescription\Repository;

use App\Database;
use PDO;

class PrescriptionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function save(array $data): ?int
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO prescriptions (patient_id, doctor_id, medical_record_id, issue_date, expiry_date, notes) 
                    VALUES (:patient_id, :doctor_id, :medical_record_id, :issue_date, :expiry_date, :notes)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':patient_id' => $data['patient_id'],
                ':doctor_id' => $data['doctor_id'],
                ':medical_record_id' => $data['medical_record_id'] ?? null,
                ':issue_date' => $data['issue_date'],
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':notes' => $data['notes'] ?? null,
            ]);
            $prescriptionId = $this->pdo->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $this->saveItems((int)$prescriptionId, $data['items']);
            }

            $this->pdo->commit();
            return (int)$prescriptionId;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Log error
            return null;
        }
    }

    private function saveItems(int $prescriptionId, array $items): void
    {
        $sql = "INSERT INTO prescription_items (prescription_id, medication_name, dosage, frequency, duration, notes) 
                VALUES (:prescription_id, :medication_name, :dosage, :frequency, :duration, :notes)";
        $stmt = $this->pdo->prepare($sql);

        foreach ($items as $item) {
            $stmt->execute([
                ':prescription_id' => $prescriptionId,
                ':medication_name' => $item['medication_name'],
                ':dosage' => $item['dosage'],
                ':frequency' => $item['frequency'],
                ':duration' => $item['duration'] ?? null,
                ':notes' => $item['notes'] ?? null,
            ]);
        }
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                CONCAT(pat.last_name, ' ', pat.first_name) as patient_name,
                CONCAT(doc.last_name, ' ', doc.first_name) as doctor_name
            FROM prescriptions p
            JOIN patients pat ON p.patient_id = pat.id
            JOIN users doc ON p.doctor_id = doc.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $prescription = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prescription) {
            $prescription['items'] = $this->findItemsByPrescriptionId($id);
        }
        return $prescription === false ? null : $prescription;
    }

    public function findItemsByPrescriptionId(int $prescriptionId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = :prescription_id");
        $stmt->execute([':prescription_id' => $prescriptionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id, p.issue_date, p.expiry_date,
                CONCAT(doc.last_name, ' ', doc.first_name) as doctor_name
            FROM prescriptions p
            JOIN users doc ON p.doctor_id = doc.id
            WHERE p.patient_id = :patient_id
            ORDER BY p.issue_date DESC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
