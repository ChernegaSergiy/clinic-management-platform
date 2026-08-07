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

namespace App\Bundles\AdminBundle\Repository;

use App\Entity\AuthConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuthConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthConfig::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM auth_configs ORDER BY provider ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findActive() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM auth_configs WHERE is_active = 1 ORDER BY provider ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM auth_configs WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function findByProvider(string $provider) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM auth_configs WHERE provider = :provider";
        $result = $conn->fetchAssociative($sql, ['provider' => $provider]);
        return $result ?: null;
    }

    public function save(array $data) : ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO auth_configs (provider, client_id, client_secret, is_active, config) 
                VALUES (:provider, :client_id, :client_secret, :is_active, :config)";

        $success = $conn->executeStatement($sql, [
            'provider' => $data['provider'],
            'client_id' => $data['client_id'] ?? null,
            'client_secret' => $data['client_secret'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'config' => $data['config'] ?? null,
        ]) > 0;

        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE auth_configs SET 
                    provider = :provider, 
                    client_id = :client_id, 
                    client_secret = :client_secret, 
                    is_active = :is_active, 
                    config = :config 
                WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'provider' => $data['provider'],
            'client_id' => $data['client_id'] ?? null,
            'client_secret' => $data['client_secret'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'config' => $data['config'] ?? null,
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM auth_configs WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
