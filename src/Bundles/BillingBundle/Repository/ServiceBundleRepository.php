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

use App\Entity\ServiceBundle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceBundleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceBundle::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM service_bundles ORDER BY name ASC";
        // @phpstan-ignore-next-line return.type (repository returns raw DB rows, not hydrated entities)
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM service_bundles WHERE id = :id";

        $bundle = $conn->fetchAssociative($sql, ['id' => $id]);

        if ($bundle) {
            $bundle['services'] = $this->getServicesInBundle($id);
        }
        return $bundle ?: null;
    }

    public function save(array $data) : ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();

        try {
            $sql = "INSERT INTO service_bundles (name, description, price, is_active) 
                    VALUES (:name, :description, :price, :is_active)";

            $conn->executeStatement($sql, [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            $bundleId = (int)$conn->lastInsertId();

            if (!empty($data['services']) && is_array($data['services'])) {
                $this->syncServices($bundleId, $data['services']);
            }

            $conn->commit();
            return $bundleId;
        } catch (\Exception $e) {
            $conn->rollBack();
            return null;
        }
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();

        try {
            $sql = "UPDATE service_bundles SET 
                        name = :name, 
                        description = :description, 
                        price = :price, 
                        is_active = :is_active 
                    WHERE id = :id";

            $success = $conn->executeStatement($sql, [
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'is_active' => $data['is_active'] ?? true,
            ]) > 0;

            if ($success && isset($data['services']) && is_array($data['services'])) {
                $this->syncServices($id, $data['services']);
            }

            $conn->commit();
            return $success;
        } catch (\Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function getServicesInBundle(int $bundleId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT s.id, s.name, s.price 
            FROM bundle_services bs
            JOIN services s ON bs.service_id = s.id
            WHERE bs.bundle_id = :bundle_id
            ORDER BY s.name ASC
        ";
        return $conn->fetchAllAssociative($sql, ['bundle_id' => $bundleId]);
    }

    private function syncServices(int $bundleId, array $serviceIds) : void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->executeStatement("DELETE FROM bundle_services WHERE bundle_id = :bundle_id", ['bundle_id' => $bundleId]);

        if (empty($serviceIds)) {
            return;
        }

        $insertSql = "INSERT INTO bundle_services (bundle_id, service_id) VALUES ";
        $values = [];
        $params = [];
        foreach ($serviceIds as $index => $serviceId) {
            $values[] = "(:bundle_id_{$index}, :service_id_{$index})";
            $params["bundle_id_{$index}"] = $bundleId;
            $params["service_id_{$index}"] = $serviceId;
        }
        $insertSql .= implode(', ', $values);

        $conn->executeStatement($insertSql, $params);
    }
}
