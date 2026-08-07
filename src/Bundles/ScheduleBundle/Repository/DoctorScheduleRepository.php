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

namespace App\Bundles\ScheduleBundle\Repository;

use App\Entity\DoctorSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctorScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DoctorSchedule::class);
    }

    public function create(array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available)
                VALUES (:doctor_id, :day_of_week, :start_time, :end_time, :is_available)";

        return $conn->executeStatement($sql, [
            'doctor_id' => $data['doctor_id'],
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_available' => $data['is_available'] ?? true,
        ]) > 0;
    }

    public function findByDoctorAndDay(int $doctorId, int $dayOfWeek) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM doctor_schedules WHERE doctor_id = :doctor_id AND day_of_week = :day_of_week";

        $result = $conn->fetchAssociative($sql, ['doctor_id' => $doctorId, 'day_of_week' => $dayOfWeek]);
        return $result ?: null;
    }

    public function findByDoctor(int $doctorId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM doctor_schedules WHERE doctor_id = :doctor_id ORDER BY day_of_week ASC";

        return $conn->fetchAllAssociative($sql, ['doctor_id' => $doctorId]);
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE doctor_schedules SET
                    doctor_id = :doctor_id,
                    day_of_week = :day_of_week,
                    start_time = :start_time,
                    end_time = :end_time,
                    is_available = :is_available
                WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'doctor_id' => $data['doctor_id'],
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_available' => $data['is_available'],
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM doctor_schedules WHERE id = :id";

        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
