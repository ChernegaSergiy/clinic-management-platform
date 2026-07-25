<?php

namespace App\Module\Prescription\Repository;

use App\Core\Event\EventDispatcherService;
use App\Event\EntityChangedEvent;
use App\Event\PatientNotificationEvent;
use App\Entity\Prescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PrescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prescription::class);
    }

    public function findAll(string $searchTerm = ''): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                p.id,
                CONCAT(pat.last_name, ' ', pat.first_name) as patient_name,
                CONCAT(doc.last_name, ' ', doc.first_name) as doctor_name,
                p.issue_date,
                p.expiry_date
            FROM prescriptions p
            JOIN patients pat ON p.patient_id = pat.id
            JOIN users doc ON p.doctor_id = doc.id
        ";

        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " WHERE CONCAT(pat.last_name, ' ', pat.first_name) LIKE :searchTerm 
                      OR CONCAT(doc.last_name, ' ', doc.first_name) LIKE :searchTerm";
            $params['searchTerm'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY p.issue_date DESC";

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function save(array $data): ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $sql = "INSERT INTO prescriptions (patient_id, doctor_id, medical_record_id, 
                                            issue_date, expiry_date, notes) 
                    VALUES (
                        :patient_id, 
                        :doctor_id, 
                        :medical_record_id, 
                        :issue_date, 
                        :expiry_date, 
                        :notes
                    )";

            $conn->executeStatement($sql, [
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'medical_record_id' => $data['medical_record_id'] ?? null,
                'issue_date' => $data['issue_date'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $prescriptionId = (int)$conn->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $this->saveItems($prescriptionId, $data['items']);
            }

            $conn->commit();
            
            EventDispatcherService::getDispatcher()->dispatch(new EntityChangedEvent('prescription', $prescriptionId, 'create', null, $data));
            EventDispatcherService::getDispatcher()->dispatch(new PatientNotificationEvent(
                $data['patient_id'],
                'prescription_created',
                'Виписано новий рецепт',
                ['prescription_id' => $prescriptionId]
            ));
            return $prescriptionId;
        } catch (\Exception $e) {
            $conn->rollBack();
            return null;
        }
    }

    private function saveItems(int $prescriptionId, array $items): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO prescription_items (prescription_id, medication_name, dosage, frequency, duration, notes) 
                VALUES (:prescription_id, :medication_name, :dosage, :frequency, :duration, :notes)";

        foreach ($items as $item) {
            $conn->executeStatement($sql, [
                'prescription_id' => $prescriptionId,
                'medication_name' => $item['medication_name'],
                'dosage' => $item['dosage'],
                'frequency' => $item['frequency'],
                'duration' => $item['duration'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    public function findById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT p.*, 
                CONCAT(pat.last_name, ' ', pat.first_name) as patient_name, 
                CONCAT(doc.last_name, ' ', doc.first_name) as doctor_name 
                FROM prescriptions p 
                JOIN patients pat ON p.patient_id = pat.id 
                JOIN users doc ON p.doctor_id = doc.id 
                WHERE p.id = :id";
                
        $prescription = $conn->fetchAssociative($sql, ['id' => $id]);

        if ($prescription) {
            $prescription['items'] = $this->findItemsByPrescriptionId($id);
        }
        return $prescription ?: null;
    }

    public function findItemsByPrescriptionId(int $prescriptionId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM prescription_items WHERE prescription_id = :prescription_id";
        return $conn->fetchAllAssociative($sql, ['prescription_id' => $prescriptionId]);
    }

    public function findByPatientId(int $patientId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT p.id, p.issue_date, p.expiry_date, 
                CONCAT(doc.last_name, ' ', doc.first_name) as doctor_name 
                FROM prescriptions p 
                JOIN users doc ON p.doctor_id = doc.id 
                WHERE p.patient_id = :patient_id 
                ORDER BY p.issue_date DESC";
                
        return $conn->fetchAllAssociative($sql, ['patient_id' => $patientId]);
    }

    public function findByDoctorId(int $doctorId, string $searchTerm = ''): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT p.id, p.issue_date, p.expiry_date, 
                       CONCAT(pat.last_name, ' ', pat.first_name) as patient_name, 
                       CONCAT(doc.last_name, ' ', doc.first_name) as doctor_name 
                FROM prescriptions p 
                JOIN patients pat ON p.patient_id = pat.id 
                JOIN users doc ON p.doctor_id = doc.id 
                WHERE p.doctor_id = :doctor_id";

        $params = ['doctor_id' => $doctorId];

        if (!empty($searchTerm)) {
            $sql .= " AND (CONCAT(pat.last_name, ' ', pat.first_name) LIKE :searchTerm)";
            $params['searchTerm'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY p.issue_date DESC";

        return $conn->fetchAllAssociative($sql, $params);
    }
}
