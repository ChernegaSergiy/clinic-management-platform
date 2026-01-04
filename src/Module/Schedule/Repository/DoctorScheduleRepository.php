<?php

namespace App\Module\Schedule\Repository;

use App\Database\Database;
use PDO;

class DoctorScheduleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available)
                VALUES (:doctor_id, :day_of_week, :start_time, :end_time, :is_available)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':doctor_id' => $data['doctor_id'],
            ':day_of_week' => $data['day_of_week'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':is_available' => $data['is_available'] ?? true,
        ]);
    }

    public function findByDoctorAndDay(int $doctorId, int $dayOfWeek): ?array
    {
        $sql = "SELECT * FROM doctor_schedules WHERE doctor_id = :doctor_id AND day_of_week = :day_of_week";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':doctor_id' => $doctorId, ':day_of_week' => $dayOfWeek]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function findByDoctor(int $doctorId): array
    {
        $sql = "SELECT * FROM doctor_schedules WHERE doctor_id = :doctor_id ORDER BY day_of_week ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':doctor_id' => $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE doctor_schedules SET
                    doctor_id = :doctor_id,
                    day_of_week = :day_of_week,
                    start_time = :start_time,
                    end_time = :end_time,
                    is_available = :is_available
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':doctor_id' => $data['doctor_id'],
            ':day_of_week' => $data['day_of_week'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':is_available' => $data['is_available'],
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM doctor_schedules WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
