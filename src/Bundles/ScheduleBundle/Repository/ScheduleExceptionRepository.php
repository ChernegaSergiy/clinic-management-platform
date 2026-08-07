<?php

namespace App\Bundles\ScheduleBundle\Repository;

use App\Entity\ScheduleException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ScheduleExceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleException::class);
    }

    public function create(array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO schedule_exceptions (doctor_id, exception_date, start_time, end_time, is_available, notes)
                VALUES (:doctor_id, :exception_date, :start_time, :end_time, :is_available, :notes)";

        return $conn->executeStatement($sql, [
            'doctor_id' => $data['doctor_id'],
            'exception_date' => $data['exception_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_available' => $data['is_available'] ?? false,
            'notes' => $data['notes'] ?? null,
        ]) > 0;
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM schedule_exceptions WHERE id = :id";

        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function findByDoctorAndDate(int $doctorId, string $date) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM schedule_exceptions WHERE doctor_id = :doctor_id AND exception_date = :exception_date ORDER BY start_time ASC";

        return $conn->fetchAllAssociative($sql, ['doctor_id' => $doctorId, 'exception_date' => $date]);
    }

    public function findByDoctorAndDateRange(int $doctorId, string $startDate, string $endDate) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM schedule_exceptions 
                WHERE doctor_id = :doctor_id 
                AND exception_date BETWEEN :start_date AND :end_date 
                ORDER BY exception_date ASC, start_time ASC";

        return $conn->fetchAllAssociative($sql, [
            'doctor_id' => $doctorId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE schedule_exceptions SET
                    doctor_id = :doctor_id,
                    exception_date = :exception_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    is_available = :is_available,
                    notes = :notes
                WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'doctor_id' => $data['doctor_id'],
            'exception_date' => $data['exception_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_available' => $data['is_available'],
            'notes' => $data['notes'],
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM schedule_exceptions WHERE id = :id";

        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
