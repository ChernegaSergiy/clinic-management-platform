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

namespace App\Bundles\BillingBundle\Repository;

use App\Entity\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM contracts ORDER BY created_at DESC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM contracts WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function save(array $data) : ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO contracts (title, description, start_date, end_date, party_a, party_b, file_path, status) 
                VALUES (:title, :description, :start_date, :end_date, :party_a, :party_b, :file_path, :status)";

        $success = $conn->executeStatement($sql, [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'party_a' => $data['party_a'] ?? null,
            'party_b' => $data['party_b'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]) > 0;

        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE contracts SET 
                    title = :title, 
                    description = :description, 
                    start_date = :start_date, 
                    end_date = :end_date, 
                    party_a = :party_a, 
                    party_b = :party_b, 
                    file_path = :file_path, 
                    status = :status 
                WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'party_a' => $data['party_a'] ?? null,
            'party_b' => $data['party_b'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM contracts WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
