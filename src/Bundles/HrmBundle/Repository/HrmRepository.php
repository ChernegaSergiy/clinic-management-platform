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

namespace App\Bundles\HrmBundle\Repository;

use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HrmRepository extends ServiceEntityRepository implements HrmRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT e.*, d.name as department_name 
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                ORDER BY e.last_name, e.first_name";
        return $conn->fetchAllAssociative($sql);
    }

    public function save(array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO employees (first_name, last_name, middle_name, position, department_id, hire_date, salary, contact_phone, status, user_id) 
                VALUES (:first_name, :last_name, :middle_name, :position, :department_id, :hire_date, :salary, :contact_phone, :status, :user_id)";

        return $conn->executeStatement($sql, [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'position' => $data['position'],
            'department_id' => $data['department_id'] ?? null,
            'hire_date' => $data['hire_date'],
            'salary' => $data['salary'] ?: null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => $data['status'] ?? 'active',
            'user_id' => $data['user_id'] ?: null,
        ]) > 0;
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT e.*, d.name as department_name 
                FROM employees e 
                LEFT JOIN departments d ON e.department_id = d.id 
                WHERE e.id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE employees SET
                    first_name = :first_name,
                    last_name = :last_name,
                    middle_name = :middle_name,
                    position = :position,
                    department_id = :department_id,
                    hire_date = :hire_date,
                    fire_date = :fire_date,
                    salary = :salary,
                    contact_phone = :contact_phone,
                    status = :status,
                    user_id = :user_id
                WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'position' => $data['position'],
            'department_id' => $data['department_id'] ?? null,
            'hire_date' => $data['hire_date'],
            'fire_date' => empty($data['fire_date']) ? null : $data['fire_date'],
            'salary' => $data['salary'] ?: null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => $data['status'] ?? 'active',
            'user_id' => $data['user_id'] ?: null,
        ]) > 0;
    }

    public function updateStatus(int $id, string $status) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE employees SET status = :status WHERE id = :id";
        return $conn->executeStatement($sql, ['status' => $status, 'id' => $id]) > 0;
    }

    public function findByUserId(int $userId) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM employees WHERE user_id = :user_id";
        $result = $conn->fetchAssociative($sql, ['user_id' => $userId]);
        return $result ?: null;
    }

    public function findByDepartment(int $departmentId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                e.*, 
                d.name as department_name, 
                dp.name as parent_name,
                u.email as user_email
            FROM employees e 
            LEFT JOIN departments d ON e.department_id = d.id 
            LEFT JOIN departments dp ON d.parent_id = dp.id 
            LEFT JOIN users u ON e.user_id = u.id
            WHERE e.department_id = :department_id 
            ORDER BY e.last_name, e.first_name
        ";

        return $conn->fetchAllAssociative($sql, ['department_id' => $departmentId]);
    }
}
