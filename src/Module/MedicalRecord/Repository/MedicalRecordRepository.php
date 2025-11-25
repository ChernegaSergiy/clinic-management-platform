<?php

namespace App\Module\MedicalRecord\Repository;

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

    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                mr.*,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                CONCAT(p.last_name, ' ', p.first_name) as patient_name
            FROM medical_records mr
            JOIN users u ON mr.doctor_id = u.id
            JOIN patients p ON mr.patient_id = p.id
            ORDER BY mr.visit_date DESC
        ");
        return $stmt->fetchAll();
    }

    public function save(array $data): int|false
    {
        $sql = "INSERT INTO medical_records (patient_id, appointment_id, doctor_id, visit_date, 
                                            diagnosis_code, diagnosis_text, treatment, notes) 
                VALUES (:patient_id, :appointment_id, :doctor_id, :visit_date, 
                        :diagnosis_code, :diagnosis_text, :treatment, :notes)";

        $stmt = $this->pdo->prepare($sql);

        $success = $stmt->execute(
            [
                ':patient_id' => $data['patient_id'],
                ':appointment_id' => $data['appointment_id'],
                ':doctor_id' => $data['doctor_id'],
                ':visit_date' => $data['visit_date'],
                ':diagnosis_code' => $data['diagnosis_code'] ?? null,
                ':diagnosis_text' => $data['diagnosis_text'] ?? null,
                ':treatment' => $data['treatment'] ?? null,
                ':notes' => $data['notes'] ?? null,
            ]
        );

        if ($success) {
            $medicalRecordId = (int)$this->pdo->lastInsertId();
            if (isset($data['icd_codes']) && is_array($data['icd_codes'])) {
                $this->attachIcdCodes($medicalRecordId, $data['icd_codes']);
            }
            if (isset($data['intervention_codes']) && is_array($data['intervention_codes'])) {
                $this->attachInterventionCodes($medicalRecordId, $data['intervention_codes']);
            }
            return $medicalRecordId;
        }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE medical_records SET
                    patient_id = :patient_id,
                    appointment_id = :appointment_id,
                    doctor_id = :doctor_id,
                    visit_date = :visit_date,
                    diagnosis_code = :diagnosis_code,
                    diagnosis_text = :diagnosis_text,
                    treatment = :treatment,
                    notes = :notes
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $success = $stmt->execute(
            [
                ':patient_id' => $data['patient_id'],
                ':appointment_id' => $data['appointment_id'],
                ':doctor_id' => $data['doctor_id'],
                ':visit_date' => $data['visit_date'],
                ':diagnosis_code' => $data['diagnosis_code'] ?? null,
                ':diagnosis_text' => $data['diagnosis_text'] ?? null,
                ':treatment' => $data['treatment'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':id' => $id,
            ]
        );

        if ($success) {
            if (isset($data['icd_codes']) && is_array($data['icd_codes'])) {
                $this->attachIcdCodes($id, $data['icd_codes']);
            }
            if (isset($data['intervention_codes']) && is_array($data['intervention_codes'])) {
                $this->attachInterventionCodes($id, $data['intervention_codes']);
            }
            return true;
        }
        return false;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                mr.*,
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM medical_records mr
            JOIN patients p ON mr.patient_id = p.id
            JOIN users u ON mr.doctor_id = u.id
            WHERE mr.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        if ($result) {
            $result['icd_codes'] = $this->getIcdCodesForMedicalRecord($id);
            $result['intervention_codes'] = $this->getInterventionCodesForMedicalRecord($id);
        }
        return $result === false ? null : $result;
    }

    public function attachIcdCodes(int $medicalRecordId, array $icdCodeIds): bool
    {
        // First, remove existing associations for this medical record
        $deleteSql = "DELETE FROM medical_record_icd WHERE medical_record_id = :medical_record_id";
        $deleteStmt = $this->pdo->prepare($deleteSql);
        $deleteStmt->execute([':medical_record_id' => $medicalRecordId]);

        if (empty($icdCodeIds)) {
            return true; // No codes to attach, but delete was successful
        }

        // Prepare for batch insert
        $insertSql = "INSERT INTO medical_record_icd (medical_record_id, icd_code_id) VALUES ";
        $values = [];
        $params = [];
        foreach ($icdCodeIds as $index => $icdCodeId) {
            $values[] = "(:medical_record_id_{$index}, :icd_code_id_{$index})";
            $params[":medical_record_id_{$index}"] = $medicalRecordId;
            $params[":icd_code_id_{$index}"] = $icdCodeId;
        }
        $insertSql .= implode(', ', $values);

        $insertStmt = $this->pdo->prepare($insertSql);
        return $insertStmt->execute($params);
    }

    public function getIcdCodesForMedicalRecord(int $medicalRecordId): array
    {
        $sql = "
            SELECT 
                ic.id,
                ic.code,
                ic.description
            FROM medical_record_icd mri
            JOIN icd_codes ic ON mri.icd_code_id = ic.id
            WHERE mri.medical_record_id = :medical_record_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':medical_record_id' => $medicalRecordId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function attachInterventionCodes(int $medicalRecordId, array $interventionCodeIds): bool
    {
        // First, remove existing associations for this medical record
        $deleteSql = "DELETE FROM medical_record_intervention WHERE medical_record_id = :medical_record_id";
        $deleteStmt = $this->pdo->prepare($deleteSql);
        $deleteStmt->execute([':medical_record_id' => $medicalRecordId]);

        if (empty($interventionCodeIds)) {
            return true; // No codes to attach, but delete was successful
        }

        // Prepare for batch insert
        $insertSql = "INSERT INTO medical_record_intervention (medical_record_id, intervention_code_id) VALUES ";
        $values = [];
        $params = [];
        foreach ($interventionCodeIds as $index => $interventionCodeId) {
            $values[] = "(:medical_record_id_{$index}, :intervention_code_id_{$index})";
            $params[":medical_record_id_{$index}"] = $medicalRecordId;
            $params[":intervention_code_id_{$index}"] = $interventionCodeId;
        }
        $insertSql .= implode(', ', $values);

        $insertStmt = $this->pdo->prepare($insertSql);
        return $insertStmt->execute($params);
    }

    public function getInterventionCodesForMedicalRecord(int $medicalRecordId): array
    {
        $sql = "
            SELECT 
                ic.id,
                ic.code,
                ic.description
            FROM medical_record_intervention mri
            JOIN intervention_codes ic ON mri.intervention_code_id = ic.id
            WHERE mri.medical_record_id = :medical_record_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':medical_record_id' => $medicalRecordId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
