<?php

namespace App\Module\Appointment\Repository;

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
        $sql = "INSERT INTO appointments (patient_id, doctor_id, start_time, end_time, status, notes, waitlist_id) 
                VALUES (:patient_id, :doctor_id, :start_time, :end_time, :status, :notes, :waitlist_id)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':doctor_id' => $data['doctor_id'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':status' => $data['status'] ?? 'scheduled', // Встановлюємо статус за замовчуванням
            ':notes' => $data['notes'] ?? null,
            ':waitlist_id' => $data['waitlist_id'] ?? null,
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

    public function findWaitlistById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                wl.*,
                COALESCE(CONCAT(p.last_name, ' ', p.first_name), 'Невідомий пацієнт') as patient_name,
                COALESCE(CONCAT(u.last_name, ' ', u.first_name), 'Будь-який') as doctor_name
            FROM waitlists wl
            LEFT JOIN patients p ON wl.patient_id = p.id
            LEFT JOIN users u ON wl.desired_doctor_id = u.id
            WHERE wl.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function updateWaitlistStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE waitlists SET status = :status WHERE id = :id");
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
                a.doctor_id,
                a.waitlist_id
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

    public function addToWaitlist(array $data): bool
    {
        $ticket = $data['ticket_number'] ?? $this->generateWaitlistTicket();
        $sql = "INSERT INTO waitlists (ticket_number, patient_id, desired_doctor_id, 
                                    desired_start_time, desired_end_time, notes, 
                                    contact_phone, contact_email) 
                VALUES (:ticket_number, :patient_id, :desired_doctor_id, 
                        :desired_start_time, :desired_end_time, :notes, 
                        :contact_phone, :contact_email)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':ticket_number' => $ticket,
            ':patient_id' => $data['patient_id'],
            ':desired_doctor_id' => $data['desired_doctor_id'] ?? null,
            ':desired_start_time' => $data['desired_start_time'] ?? null,
            ':desired_end_time' => $data['desired_end_time'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
        ]);
    }

    public function getWaitlistEntries(?string $status = 'pending'): array
    {
        $sql = "SELECT 
                    wl.id,
                    wl.ticket_number,
                    COALESCE(CONCAT(p.last_name, ' ', p.first_name), 'Невідомий пацієнт') as patient_name,
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateWaitlistTicket(): string
    {
        $year = date('Y');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM waitlists WHERE YEAR(created_at) = :year");
        $stmt->execute([':year' => $year]);
        $count = (int)$stmt->fetchColumn() + 1;
        return sprintf('WL-%s-%05d', $year, $count);
    }

    public function isPatientAssignedToDoctor(int $patientId, int $doctorId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 
            FROM appointments 
            WHERE patient_id = :patient_id AND doctor_id = :doctor_id
            LIMIT 1
        ");
        $stmt->execute([
            ':patient_id' => $patientId,
            ':doctor_id' => $doctorId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public function isAppointmentOwnedByDoctor(int $appointmentId, int $doctorId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 
            FROM appointments 
            WHERE id = :appointment_id AND doctor_id = :doctor_id
            LIMIT 1
        ");
        $stmt->execute([
            ':appointment_id' => $appointmentId,
            ':doctor_id' => $doctorId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public function findAppointmentsForReminder(int $minutesBefore): array
    {
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':minutes_before' => $minutesBefore]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDoctorDailyLoad(string $date): array
    {
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
            ORDER BY total_appointments DESC;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUpcoming(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT "
            . "a.id, "
            . "CONCAT(p.last_name, ' ', p.first_name) as patient_name, "
            . "CONCAT(u.last_name, ' ', u.first_name) as doctor_name, "
            . "a.start_time, "
            . "a.end_time, "
            . "a.status, "
            . "a.doctor_id "
            . "FROM appointments a "
            . "JOIN patients p ON a.patient_id = p.id "
            . "JOIN users u ON a.doctor_id = u.id "
            . "WHERE a.start_time > NOW() AND a.status = 'scheduled' "
            . "ORDER BY a.start_time ASC "
            . "LIMIT 10" // Limit to next 10 upcoming appointments for dashboard
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAppointmentsByDate(string $date): int
    {
        $sql = "SELECT COUNT(*) FROM appointments WHERE DATE(start_time) = :date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Retrieves the sum of durations of completed appointments for each doctor on a given date.
     *
     * @param string $date The date in 'YYYY-MM-DD' format.
     * @return array An associative array with doctor_id as key and total duration in seconds as value.
     */
    public function getSumOfCompletedAppointmentDurationsForDate(string $date): array
    {
        $sql = "
            SELECT
                doctor_id,
                SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))) as total_duration_seconds
            FROM appointments
            WHERE DATE(start_time) = :date AND status = 'completed'
            GROUP BY doctor_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches as [doctor_id => total_duration_seconds]
        return array_map('intval', $results); // Ensure values are integers
    }

    /**
     * Retrieves completed appointments for a given date, including associated medical record and ICD codes.
     * These appointments serve as "discharge events" for readmission tracking.
     *
     * @param string $date The date in 'YYYY-MM-DD' format.
     * @return array An array of associative arrays, each representing a completed appointment.
     */
    public function getCompletedAppointmentsWithIcdCodes(string $date): array
    {
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds subsequent appointments for a specific patient after a given date and within a timeframe.
     * Used to detect potential readmissions.
     *
     * @param int $patientId The ID of the patient.
     * @param string $afterDate The date (YYYY-MM-DD) after which to search for appointments.
     * @param int $timeframeDays The number of days after $afterDate to consider.
     * @return array An array of subsequent appointments.
     */
    public function findPatientSubsequentAppointments(int $patientId, string $afterDate, int $timeframeDays): array
    {
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':patient_id' => $patientId,
            ':after_date' => $afterDate,
            ':timeframe_days' => $timeframeDays,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
