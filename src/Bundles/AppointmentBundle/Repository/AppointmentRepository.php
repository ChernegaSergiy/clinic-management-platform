<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Bundles\AppointmentBundle\Repository;

use App\Entity\Appointment;
use App\Event\EntityChangedEvent;
use App\Event\PatientNotificationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AppointmentRepository extends ServiceEntityRepository implements AppointmentRepositoryInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(ManagerRegistry $registry, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($registry, Appointment::class);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function findAll() : array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.id', 'a.start_time', 'a.end_time', 'a.status', 'IDENTITY(a.doctor) as doctor_id', 'a.room_id')
            ->addSelect("CONCAT(p.last_name, ' ', p.first_name) as patient_name")
            ->addSelect("CONCAT(u.last_name, ' ', u.first_name) as doctor_name")
            ->addSelect("r.name as room_name")
            ->join('a.patient', 'p')
            ->join('a.doctor', 'u')
            ->leftJoin(\App\Entity\Room::class, 'r', 'WITH', 'a.room_id = r.id')
            ->orderBy('a.start_time', 'DESC');

        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as &$row) {
            if (isset($row['start_time']) && $row['start_time'] instanceof \DateTimeInterface) {
                $row['start_time'] = $row['start_time']->format('Y-m-d H:i:s');
            }
            if (isset($row['end_time']) && $row['end_time'] instanceof \DateTimeInterface) {
                $row['end_time'] = $row['end_time']->format('Y-m-d H:i:s');
            }
        }

        return $results;
    }

    public function save(array $data) : int|false
    {
        $appointment = new Appointment();

        $patient = $this->getEntityManager()->getReference(\App\Entity\Patient::class, $data['patient_id']);
        $appointment->setPatient($patient);

        $doctor = $this->getEntityManager()->getReference(\App\Entity\User::class, $data['doctor_id']);
        $appointment->setDoctor($doctor);

        try {
            $appointment->setStartTime(new \DateTime($data['start_time']));
            $appointment->setEndTime(new \DateTime($data['end_time']));
        } catch (\Exception $e) {
            return false;
        }

        $appointment->setStatus($data['status'] ?? 'scheduled');

        if (array_key_exists('notes', $data)) {
            $appointment->setNotes($data['notes']);
        }

        if (!empty($data['waitlist_id'])) {
            $appointment->setWaitlistId($data['waitlist_id']);
        }

        if (!empty($data['room_id'])) {
            $appointment->setRoomId($data['room_id']);
        }

        try {
            $this->getEntityManager()->persist($appointment);
            $this->getEntityManager()->flush();

            $appointmentId = $appointment->getId();

            $this->eventDispatcher->dispatch(new EntityChangedEvent('appointment', $appointmentId, 'create', null, $data));
            $this->eventDispatcher->dispatch(new PatientNotificationEvent(
                $data['patient_id'],
                'appointment_scheduled',
                'Ваш прийом заплановано на ' . $data['start_time'],
                ['appointment_id' => $appointmentId]
            ));

            return $appointmentId;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function findById(int $id) : ?array
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                'a.id',
                'a.start_time',
                'a.end_time',
                'a.status',
                'a.notes',
                'a.waitlist_id',
                'a.room_id',
                'a.ehealth_episode_id',
                'a.created_at',
                'a.updated_at',
                'IDENTITY(a.patient) as patient_id',
                'IDENTITY(a.doctor) as doctor_id'
            )
            ->addSelect("CONCAT(p.last_name, ' ', p.first_name) as patient_name")
            ->addSelect("CONCAT(u.last_name, ' ', u.first_name) as doctor_name")
            ->addSelect("r.name as room_name")
            ->join('a.patient', 'p')
            ->join('a.doctor', 'u')
            ->leftJoin(\App\Entity\Room::class, 'r', 'WITH', 'a.room_id = r.id')
            ->where('a.id = :id')
            ->setParameter('id', $id);

        $result = $qb->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        if ($result) {
            foreach (['start_time', 'end_time', 'created_at', 'updated_at'] as $field) {
                if (isset($result[$field]) && $result[$field] instanceof \DateTimeInterface) {
                    $result[$field] = $result[$field]->format('Y-m-d H:i:s');
                }
            }
        }

        return $result;
    }

    public function update(int $id, array $data) : bool
    {
        $oldAppointment = $this->findById($id);
        if (!$oldAppointment) {
            return false;
        }

        /** @var Appointment|null $appointment */
        $appointment = $this->find($id);
        if (!$appointment) {
            return false;
        }

        if (isset($data['patient_id'])) {
            $patient = $this->getEntityManager()->getReference(\App\Entity\Patient::class, $data['patient_id']);
            $appointment->setPatient($patient);
        }
        if (isset($data['doctor_id'])) {
            $doctor = $this->getEntityManager()->getReference(\App\Entity\User::class, $data['doctor_id']);
            $appointment->setDoctor($doctor);
        }

        if (isset($data['start_time'])) {
            try {
                $appointment->setStartTime(new \DateTime($data['start_time']));
            } catch (\Exception $e) {
            }
        }
        if (isset($data['end_time'])) {
            try {
                $appointment->setEndTime(new \DateTime($data['end_time']));
            } catch (\Exception $e) {
            }
        }

        if (isset($data['status'])) {
            $appointment->setStatus($data['status']);
        }
        if (array_key_exists('notes', $data)) {
            $appointment->setNotes($data['notes']);
        }
        if (array_key_exists('room_id', $data)) {
            $appointment->setRoomId(empty($data['room_id']) ? null : $data['room_id']);
        }

        try {
            $this->getEntityManager()->flush();
            $this->eventDispatcher->dispatch(new EntityChangedEvent('appointment', $id, 'update', $oldAppointment, $data));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateStatus(int $id, string $status) : bool
    {
        $oldAppointment = $this->findById($id);
        if (!$oldAppointment) {
            return false;
        }

        /** @var Appointment|null $appointment */
        $appointment = $this->find($id);
        if (!$appointment) {
            return false;
        }

        $appointment->setStatus($status);

        try {
            $this->getEntityManager()->flush();
            if ($oldAppointment['status'] !== $status) {
                $this->eventDispatcher->dispatch(new EntityChangedEvent('appointment', $id, 'update', $oldAppointment, ['status' => $status]));
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function findWaitlistById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                wl.*,
                COALESCE(CONCAT(p.last_name, ' ', p.first_name), 'Невідомий пацієнт') as patient_name,
                COALESCE(CONCAT(u.last_name, ' ', u.first_name), 'Будь-який') as doctor_name
            FROM waitlists wl
            LEFT JOIN patients p ON wl.patient_id = p.id
            LEFT JOIN users u ON wl.desired_doctor_id = u.id
            WHERE wl.id = :id
        ";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function updateWaitlistStatus(int $id, string $status) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        return $conn->executeStatement("UPDATE waitlists SET status = :status WHERE id = :id", ['status' => $status, 'id' => $id]) > 0;
    }

    public function findByPatientId(int $patientId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                a.*, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.doctor_id = u.id
            WHERE a.patient_id = :patient_id
            ORDER BY a.start_time DESC
        ";
        return $conn->fetchAllAssociative($sql, ['patient_id' => $patientId]);
    }

    public function findByDateRange(string $start, string $end) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                a.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                a.start_time, 
                a.end_time, 
                a.status,
                a.doctor_id,
                a.waitlist_id,
                a.room_id,
                r.name as room_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            LEFT JOIN rooms r ON a.room_id = r.id
            WHERE a.start_time >= :start_time AND a.end_time <= :end_time
            ORDER BY a.start_time ASC
        ";
        return $conn->fetchAllAssociative($sql, ['start_time' => $start, 'end_time' => $end]);
    }

    public function addToWaitlist(array $data) : bool
    {
        $ticket = $data['ticket_number'] ?? $this->generateWaitlistTicket();
        $conn = $this->getEntityManager()->getConnection();

        $sql = "INSERT INTO waitlists (ticket_number, patient_id, desired_doctor_id, 
                                    desired_start_time, desired_end_time, notes, 
                                    contact_phone, contact_email) 
                VALUES (:ticket_number, :patient_id, :desired_doctor_id, 
                        :desired_start_time, :desired_end_time, :notes, 
                        :contact_phone, :contact_email)";

        return $conn->executeStatement($sql, [
            'ticket_number' => $ticket,
            'patient_id' => $data['patient_id'],
            'desired_doctor_id' => $data['desired_doctor_id'] ?? null,
            'desired_start_time' => $data['desired_start_time'] ?? null,
            'desired_end_time' => $data['desired_end_time'] ?? null,
            'notes' => $data['notes'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
        ]) > 0;
    }

    public function getWaitlistEntries(?string $status = 'pending') : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT 
                    wl.id,
                    wl.ticket_number,
                    COALESCE(CONCAT(p.last_name, ' ', p.first_name), 'Невідомий пацієнт') as patient_name,
                    p.status as patient_status,
                    p.phone as patient_phone,
                    p.email as patient_email,
                    COALESCE(CONCAT(u.last_name, ' ', u.first_name), 'Будь-який') as doctor_name,
                    wl.desired_start_time,
                    wl.desired_end_time,
                    wl.notes,
                    wl.contact_phone,
                    wl.contact_email,
                    wl.status,
                    wl.created_at
                FROM waitlists wl
                LEFT JOIN patients p ON wl.patient_id = p.id
                LEFT JOIN users u ON wl.desired_doctor_id = u.id
                WHERE (:status IS NULL OR wl.status = :status)
                ORDER BY wl.created_at ASC";

        return $conn->fetchAllAssociative($sql, ['status' => $status]);
    }

    public function generateWaitlistTicket() : string
    {
        $conn = $this->getEntityManager()->getConnection();
        $year = date('Y');
        $count = (int)$conn->fetchOne("SELECT COUNT(*) FROM waitlists WHERE YEAR(created_at) = :year", ['year' => $year]) + 1;
        return sprintf('WL-%s-%05d', $year, $count);
    }

    public function isPatientAssignedToDoctor(int $patientId, int $doctorId) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT 1 FROM appointments WHERE patient_id = :patient_id AND doctor_id = :doctor_id LIMIT 1";
        return (bool)$conn->fetchOne($sql, ['patient_id' => $patientId, 'doctor_id' => $doctorId]);
    }

    public function isAppointmentOwnedByDoctor(int $appointmentId, int $doctorId) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT 1 FROM appointments WHERE id = :appointment_id AND doctor_id = :doctor_id LIMIT 1";
        return (bool)$conn->fetchOne($sql, ['appointment_id' => $appointmentId, 'doctor_id' => $doctorId]);
    }

    public function findAppointmentsForReminder(int $minutesBefore) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                a.id, 
                a.patient_id,
                a.doctor_id,
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                a.start_time, 
                a.end_time
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            WHERE a.status = 'scheduled' 
              AND a.start_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :minutes_before MINUTE)
        ";
        return $conn->fetchAllAssociative($sql, ['minutes_before' => $minutesBefore]);
    }

    public function findByDoctorId(int $doctorId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                a.*, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            WHERE a.doctor_id = :doctor_id
            ORDER BY a.start_time DESC
        ";
        return $conn->fetchAllAssociative($sql, ['doctor_id' => $doctorId]);
    }

    public function findByDoctorIdAndDateRange(int $doctorId, string $start, string $end) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                a.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                a.start_time, 
                a.end_time, 
                a.status,
                a.doctor_id,
                a.waitlist_id
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            WHERE a.doctor_id = :doctor_id
              AND a.start_time >= :start_time AND a.start_time <= :end_time
            ORDER BY a.start_time ASC
        ";
        return $conn->fetchAllAssociative($sql, ['doctor_id' => $doctorId, 'start_time' => $start, 'end_time' => $end]);
    }

    public function findPatientIdsByDoctor(int $doctorId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DISTINCT patient_id FROM appointments WHERE doctor_id = :doctor_id";
        $results = $conn->fetchFirstColumn($sql, ['doctor_id' => $doctorId]);
        return array_map('intval', $results);
    }

    public function countScheduledByDate(string $date) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM appointments WHERE status = 'scheduled' AND DATE(start_time) = :date";
        return (int)$conn->fetchOne($sql, ['date' => $date]);
    }

    public function countScheduledByRange(string $from, string $to) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM appointments WHERE status = 'scheduled' AND DATE(start_time) BETWEEN :from AND :to";
        return (int)$conn->fetchOne($sql, ['from' => $from, 'to' => $to]);
    }

    public function sumBookedHoursByRange(string $from, string $to) : float
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))) / 3600, 0) as hours FROM appointments WHERE status = 'scheduled' AND DATE(start_time) BETWEEN :from AND :to";
        return (float)$conn->fetchOne($sql, ['from' => $from, 'to' => $to]);
    }

    public function countDistinctDoctorsByRange(string $from, string $to) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(DISTINCT doctor_id) FROM appointments WHERE status = 'scheduled' AND DATE(start_time) BETWEEN :from AND :to";
        return (int)$conn->fetchOne($sql, ['from' => $from, 'to' => $to]);
    }

    public function countDistinctPatientsByRange(string $from, string $to) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE status = 'scheduled' AND DATE(start_time) BETWEEN :from AND :to";
        return (int)$conn->fetchOne($sql, ['from' => $from, 'to' => $to]);
    }

    public function countReadmittedPatients(string $from, string $to) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT COUNT(*) FROM (
                SELECT patient_id, COUNT(*) as cnt
                FROM appointments
                WHERE status = 'scheduled' AND DATE(start_time) BETWEEN :from AND :to
                GROUP BY patient_id
                HAVING cnt > 1
            ) t
        ";
        return (int)$conn->fetchOne($sql, ['from' => $from, 'to' => $to]);
    }

    public function getDoctorDailyLoad(string $date) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                u.id as doctor_id,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                COUNT(a.id) as total_appointments,
                SUM(TIME_TO_SEC(TIMEDIFF(a.end_time, a.start_time))) / 3600 as total_hours_booked
            FROM users u
            LEFT JOIN appointments a ON u.id = a.doctor_id
                AND DATE(a.start_time) = :date
                AND a.status = 'scheduled'
            WHERE u.role_id = (SELECT id FROM roles WHERE name = 'doctor')
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_appointments DESC
        ";
        return $conn->fetchAllAssociative($sql, ['date' => $date]);
    }

    public function findUpcoming() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
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
            WHERE a.start_time > NOW() AND a.status = 'scheduled' 
            ORDER BY a.start_time ASC 
            LIMIT 10
        ";
        return $conn->fetchAllAssociative($sql);
    }

    public function countAppointmentsByDate(string $date) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM appointments WHERE DATE(start_time) = :date";
        return (int)$conn->fetchOne($sql, ['date' => $date]);
    }

    public function getSumOfCompletedAppointmentDurationsForDate(string $date) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                doctor_id,
                SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))) as total_duration_seconds
            FROM appointments
            WHERE DATE(start_time) = :date AND status = 'completed'
            GROUP BY doctor_id
        ";
        $results = $conn->fetchAllKeyValue($sql, ['date' => $date]);
        return array_map('intval', $results);
    }

    public function getCompletedAppointmentsWithIcdCodes(string $date) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                a.id as appointment_id,
                a.patient_id,
                mr.id as medical_record_id,
                GROUP_CONCAT(mri.icd_code_id) as icd_code_ids
            FROM appointments a
            JOIN medical_records mr ON a.id = mr.appointment_id
            LEFT JOIN medical_record_icd mri ON mr.id = mri.medical_record_id
            WHERE DATE(a.end_time) = :date AND a.status = 'completed'
            GROUP BY a.id, a.patient_id, mr.id
        ";
        return $conn->fetchAllAssociative($sql, ['date' => $date]);
    }

    public function findPatientSubsequentAppointments(int $patientId, string $afterDate, int $timeframeDays) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                a.id as appointment_id,
                a.start_time,
                a.status,
                mr.id as medical_record_id
            FROM appointments a
            LEFT JOIN medical_records mr ON a.id = mr.appointment_id
            WHERE a.patient_id = :patient_id
              AND a.start_time > :after_date
              AND a.start_time <= DATE_ADD(:after_date, INTERVAL :timeframe_days DAY)
            ORDER BY a.start_time ASC
        ";
        return $conn->fetchAllAssociative($sql, [
            'patient_id' => $patientId,
            'after_date' => $afterDate,
            'timeframe_days' => $timeframeDays,
        ]);
    }

    public function findByRoomIdAndDateRange(int $roomId, string $start, string $end) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                a.id, 
                a.start_time, 
                a.end_time, 
                a.status,
                a.room_id
            FROM appointments a
            WHERE a.room_id = :room_id
            AND a.start_time < :end_time AND a.end_time > :start_time
            ORDER BY a.start_time ASC
        ";
        return $conn->fetchAllAssociative($sql, [
            'room_id' => $roomId,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
