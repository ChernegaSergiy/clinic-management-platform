<?php

namespace App\Module\Schedule\Repository;

use App\Database\Database;
use PDO;

class ScheduleExceptionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO schedule_exceptions (doctor_id, exception_date, start_time, end_time, is_available, notes)
                VALUES (:doctor_id, :exception_date, :start_time, :end_time, :is_available, :notes)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':doctor_id' => $data['doctor_id'],
            ':exception_date' => $data['exception_date'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':is_available' => $data['is_available'] ?? false,
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM schedule_exceptions WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function findByDoctorAndDate(int $doctorId, string $date): array
    {
        $sql = "SELECT * FROM schedule_exceptions WHERE doctor_id = :doctor_id AND exception_date = :exception_date ORDER BY start_time ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':doctor_id' => $doctorId, ':exception_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByDoctorAndDateRange(int $doctorId, string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM schedule_exceptions 
                WHERE doctor_id = :doctor_id 
                AND exception_date BETWEEN :start_date AND :end_date 
                ORDER BY exception_date ASC, start_time ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':doctor_id' => $doctorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE schedule_exceptions SET
                    doctor_id = :doctor_id,
                    exception_date = :exception_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    is_available = :is_available,
                    notes = :notes
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':doctor_id' => $data['doctor_id'],
            ':exception_date' => $data['exception_date'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':is_available' => $data['is_available'],
            ':notes' => $data['notes'],
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM schedule_exceptions WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
