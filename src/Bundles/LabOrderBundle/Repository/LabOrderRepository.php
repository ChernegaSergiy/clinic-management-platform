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

namespace App\Bundles\LabOrderBundle\Repository;

use App\Entity\LabOrder;
use App\Event\EntityChangedEvent;
use App\Event\PatientNotificationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LabOrderRepository extends ServiceEntityRepository implements LabOrderRepositoryInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(ManagerRegistry $registry, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($registry, LabOrder::class);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function findByMedicalRecordId(int $medicalRecordId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT *
            FROM lab_orders
            WHERE medical_record_id = :medical_record_id
            ORDER BY created_at DESC
        ";
        return $conn->fetchAllAssociative($sql, ['medical_record_id' => $medicalRecordId]);
    }

    public function save(array $data) : int|false
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO lab_orders (patient_id, doctor_id, medical_record_id, 
                                        order_code, results, status) 
                VALUES (:patient_id, :doctor_id, :medical_record_id, 
                        :order_code, :results, :status)";

        $success = $conn->executeStatement($sql, [
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'medical_record_id' => $data['medical_record_id'],
            'order_code' => $data['order_code'],
            'results' => $data['results'] ?? null,
            'status' => $data['status'] ?? 'ordered',
        ]) > 0;

        if ($success) {
            $labOrderId = (int)$conn->lastInsertId();
            $this->eventDispatcher->dispatch(new EntityChangedEvent('lab_order', $labOrderId, 'create', null, $data));
            $this->eventDispatcher->dispatch(new PatientNotificationEvent(
                $data['patient_id'],
                'lab_order_created',
                'Створено замовлення на лабораторні дослідження',
                ['lab_order_id' => $labOrderId]
            ));
            return $labOrderId;
        }
        return false;
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                lo.*,
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name
            FROM lab_orders lo
            JOIN patients p ON lo.patient_id = p.id
            JOIN users u ON lo.doctor_id = u.id
            WHERE lo.id = :id
        ";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function update(int $id, array $data) : bool
    {
        $oldLabOrder = $this->findById($id);
        if (!$oldLabOrder) {
            return false;
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE lab_orders SET 
                    order_code = :order_code, 
                    status = :status, 
                    results = :results, 
                    notes = :notes 
                WHERE id = :id";

        $result = $conn->executeStatement($sql, [
            'id' => $id,
            'order_code' => $data['order_code'],
            'status' => $data['status'],
            'results' => $data['results'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]) > 0;

        if ($result) {
            $this->eventDispatcher->dispatch(new EntityChangedEvent('lab_order', $id, 'update', $oldLabOrder, $data));
        }

        return $result;
    }

    public function updateQrCodeHash(int $id, string $qrCodeHash) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE lab_orders SET qr_code_hash = :qr_code_hash WHERE id = :id";
        return $conn->executeStatement($sql, [
            'qr_code_hash' => $qrCodeHash,
            'id' => $id,
        ]) > 0;
    }

    public function countByStatus(array $statuses) : int
    {
        if (empty($statuses)) {
            return 0;
        }
        $conn = $this->getEntityManager()->getConnection();

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "SELECT COUNT(*) FROM lab_orders WHERE status IN ($placeholders)";

        $stmt = $conn->executeQuery($sql, $statuses);
        return (int)$stmt->fetchOne();
    }
}
