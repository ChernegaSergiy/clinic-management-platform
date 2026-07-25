<?php

namespace App\Module\MedicalRecord\Repository;

use App\Entity\MedicalRecord;
use App\Event\EntityChangedEvent;
use App\Event\PatientNotificationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use PDO;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MedicalRecordRepository extends ServiceEntityRepository implements MedicalRecordRepositoryInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(ManagerRegistry $registry, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($registry, MedicalRecord::class);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function findByPatientId(int $patientId): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->select(
                'mr.id', 'IDENTITY(mr.patient) as patient_id', 'IDENTITY(mr.appointment) as appointment_id', 
                'IDENTITY(mr.doctor) as doctor_id', 'mr.visit_date', 
                'mr.diagnosis_code', 'mr.diagnosis_text', 'mr.treatment', 'mr.notes',
                "CONCAT(u.last_name, ' ', u.first_name) as doctor_name",
                "CONCAT(p.last_name, ' ', p.first_name) as patient_name"
            )
            ->join('mr.doctor', 'u')
            ->join('mr.patient', 'p')
            ->where('mr.patient = :patient_id')
            ->setParameter('patient_id', $patientId)
            ->orderBy('mr.visit_date', 'DESC');

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function findAll(string $searchTerm = ''): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->select(
                'mr.id', 'IDENTITY(mr.patient) as patient_id', 'IDENTITY(mr.appointment) as appointment_id', 
                'IDENTITY(mr.doctor) as doctor_id', 'mr.visit_date', 
                'mr.diagnosis_code', 'mr.diagnosis_text', 'mr.treatment', 'mr.notes',
                "CONCAT(u.last_name, ' ', u.first_name) as doctor_name",
                "CONCAT(p.last_name, ' ', p.first_name) as patient_name"
            )
            ->join('mr.doctor', 'u')
            ->join('mr.patient', 'p');

        if (!empty($searchTerm)) {
            $qb->where("CONCAT(p.last_name, ' ', p.first_name) LIKE :searchTerm")
               ->orWhere("CONCAT(u.last_name, ' ', u.first_name) LIKE :searchTerm")
               ->orWhere('mr.diagnosis_code LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $qb->orderBy('mr.visit_date', 'DESC');

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function findByDoctorId(int $doctorId, string $searchTerm = ''): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->select(
                'mr.id', 'IDENTITY(mr.patient) as patient_id', 'IDENTITY(mr.appointment) as appointment_id', 
                'IDENTITY(mr.doctor) as doctor_id', 'mr.visit_date', 
                'mr.diagnosis_code', 'mr.diagnosis_text', 'mr.treatment', 'mr.notes',
                "CONCAT(u.last_name, ' ', u.first_name) as doctor_name",
                "CONCAT(p.last_name, ' ', p.first_name) as patient_name"
            )
            ->join('mr.doctor', 'u')
            ->join('mr.patient', 'p')
            ->where('mr.doctor = :doctor_id')
            ->setParameter('doctor_id', $doctorId);

        if (!empty($searchTerm)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like("CONCAT(p.last_name, ' ', p.first_name)", ':searchTerm'),
                    $qb->expr()->like('mr.diagnosis_code', ':searchTerm')
                )
            )->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $qb->orderBy('mr.visit_date', 'DESC');

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function save(array $data): int|false
    {
        $mr = new MedicalRecord();
        
        $patient = $this->getEntityManager()->getReference(\App\Entity\Patient::class, $data['patient_id']);
        $mr->setPatient($patient);
        
        $doctor = $this->getEntityManager()->getReference(\App\Entity\User::class, $data['doctor_id']);
        $mr->setDoctor($doctor);

        if (!empty($data['appointment_id'])) {
            $appointment = $this->getEntityManager()->getReference(\App\Entity\Appointment::class, $data['appointment_id']);
            $mr->setAppointment($appointment);
        }

        if (!empty($data['visit_date'])) {
            try {
                $mr->setVisitDate(new \DateTime($data['visit_date']));
            } catch (\Exception $e) {}
        }
        
        if (array_key_exists('diagnosis_code', $data)) {
            $mr->setDiagnosisCode($data['diagnosis_code']);
        }
        if (array_key_exists('diagnosis_text', $data)) {
            $mr->setDiagnosisText($data['diagnosis_text']);
        }
        if (array_key_exists('treatment', $data)) {
            $mr->setTreatment($data['treatment']);
        }
        if (array_key_exists('notes', $data)) {
            $mr->setNotes($data['notes']);
        }

        try {
            $this->getEntityManager()->persist($mr);
            $this->getEntityManager()->flush();

            $medicalRecordId = $mr->getId();

            if (isset($data['icd_codes']) && is_array($data['icd_codes'])) {
                $this->attachIcdCodes($medicalRecordId, $data['icd_codes']);
            }
            if (isset($data['intervention_codes']) && is_array($data['intervention_codes'])) {
                $this->attachInterventionCodes($medicalRecordId, $data['intervention_codes']);
            }
            
            $this->eventDispatcher->dispatch(new EntityChangedEvent('medical_record', $medicalRecordId, 'create', null, $data));
            $this->eventDispatcher->dispatch(new PatientNotificationEvent(
                $data['patient_id'],
                'medical_record_created',
                'Створено новий медичний запис після візиту',
                ['medical_record_id' => $medicalRecordId]
            ));
            
            return $medicalRecordId;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $oldMedicalRecord = $this->findById($id);
        if (!$oldMedicalRecord) {
            return false;
        }

        /** @var MedicalRecord|null $mr */
        $mr = $this->find($id);
        if (!$mr) {
            return false;
        }

        if (isset($data['patient_id'])) {
            $patient = $this->getEntityManager()->getReference(\App\Entity\Patient::class, $data['patient_id']);
            $mr->setPatient($patient);
        }
        
        if (isset($data['doctor_id'])) {
            $doctor = $this->getEntityManager()->getReference(\App\Entity\User::class, $data['doctor_id']);
            $mr->setDoctor($doctor);
        }

        if (array_key_exists('appointment_id', $data)) {
            if (!empty($data['appointment_id'])) {
                $appointment = $this->getEntityManager()->getReference(\App\Entity\Appointment::class, $data['appointment_id']);
                $mr->setAppointment($appointment);
            } else {
                $mr->setAppointment(null);
            }
        }

        if (!empty($data['visit_date'])) {
            try {
                $mr->setVisitDate(new \DateTime($data['visit_date']));
            } catch (\Exception $e) {}
        }
        
        if (array_key_exists('diagnosis_code', $data)) {
            $mr->setDiagnosisCode($data['diagnosis_code']);
        }
        if (array_key_exists('diagnosis_text', $data)) {
            $mr->setDiagnosisText($data['diagnosis_text']);
        }
        if (array_key_exists('treatment', $data)) {
            $mr->setTreatment($data['treatment']);
        }
        if (array_key_exists('notes', $data)) {
            $mr->setNotes($data['notes']);
        }

        try {
            $this->getEntityManager()->flush();

            if (isset($data['icd_codes']) && is_array($data['icd_codes'])) {
                $this->attachIcdCodes($id, $data['icd_codes']);
            }
            if (isset($data['intervention_codes']) && is_array($data['intervention_codes'])) {
                $this->attachInterventionCodes($id, $data['intervention_codes']);
            }
            $this->eventDispatcher->dispatch(new EntityChangedEvent('medical_record', $id, 'update', $oldMedicalRecord, $data));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function findById(int $id): ?array
    {
        $qb = $this->createQueryBuilder('mr')
            ->select(
                'mr.id', 'IDENTITY(mr.patient) as patient_id', 'IDENTITY(mr.appointment) as appointment_id', 
                'IDENTITY(mr.doctor) as doctor_id', 'mr.visit_date', 
                'mr.diagnosis_code', 'mr.diagnosis_text', 'mr.treatment', 'mr.notes',
                "CONCAT(u.last_name, ' ', u.first_name) as doctor_name",
                "CONCAT(p.last_name, ' ', p.first_name) as patient_name"
            )
            ->join('mr.doctor', 'u')
            ->join('mr.patient', 'p')
            ->where('mr.id = :id')
            ->setParameter('id', $id);

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);

        if ($result) {
            $result['icd_codes'] = $this->getIcdCodesForMedicalRecord($id);
            $result['intervention_codes'] = $this->getInterventionCodesForMedicalRecord($id);
        }
        return $result;
    }

    public function attachIcdCodes(int $medicalRecordId, array $icdCodeIds): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $conn->executeStatement(
            "DELETE FROM medical_record_icd WHERE medical_record_id = :medical_record_id",
            ['medical_record_id' => $medicalRecordId]
        );

        $icdCodeIds = array_filter($icdCodeIds, fn ($id) => is_numeric($id) && $id > 0);

        if (empty($icdCodeIds)) {
            return true;
        }

        $insertSql = "INSERT INTO medical_record_icd (medical_record_id, icd_code_id) VALUES ";
        $values = [];
        $params = [];
        foreach ($icdCodeIds as $index => $icdCodeId) {
            $values[] = "(:medical_record_id_{$index}, :icd_code_id_{$index})";
            $params["medical_record_id_{$index}"] = $medicalRecordId;
            $params["icd_code_id_{$index}"] = $icdCodeId;
        }
        $insertSql .= implode(', ', $values);

        return $conn->executeStatement($insertSql, $params) > 0;
    }

    public function getIcdCodesForMedicalRecord(int $medicalRecordId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                ic.id,
                ic.code,
                ic.description
            FROM medical_record_icd mri
            JOIN icd_codes ic ON mri.icd_code_id = ic.id
            WHERE mri.medical_record_id = :medical_record_id
        ";
        return $conn->fetchAllAssociative($sql, ['medical_record_id' => $medicalRecordId]);
    }

    public function attachInterventionCodes(int $medicalRecordId, array $interventionCodeIds): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $conn->executeStatement(
            "DELETE FROM medical_record_intervention WHERE medical_record_id = :medical_record_id",
            ['medical_record_id' => $medicalRecordId]
        );

        $interventionCodeIds = array_filter($interventionCodeIds, fn ($id) => is_numeric($id) && $id > 0);

        if (empty($interventionCodeIds)) {
            return true;
        }

        $insertSql = "INSERT INTO medical_record_intervention (medical_record_id, intervention_code_id) VALUES ";
        $values = [];
        $params = [];
        foreach ($interventionCodeIds as $index => $interventionCodeId) {
            $values[] = "(:medical_record_id_{$index}, :intervention_code_id_{$index})";
            $params["medical_record_id_{$index}"] = $medicalRecordId;
            $params["intervention_code_id_{$index}"] = $interventionCodeId;
        }
        $insertSql .= implode(', ', $values);

        return $conn->executeStatement($insertSql, $params) > 0;
    }

    public function getInterventionCodesForMedicalRecord(int $medicalRecordId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                ic.id,
                ic.code,
                ic.description
            FROM medical_record_intervention mri
            JOIN intervention_codes ic ON mri.intervention_code_id = ic.id
            WHERE mri.medical_record_id = :medical_record_id
        ";
        return $conn->fetchAllAssociative($sql, ['medical_record_id' => $medicalRecordId]);
    }
}
