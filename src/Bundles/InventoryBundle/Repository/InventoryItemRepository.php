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

namespace App\Bundles\InventoryBundle\Repository;

use App\Bundles\BillingBundle\Repository\InvoiceRepository;
use App\Entity\InventoryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryItemRepository extends ServiceEntityRepository implements InventoryItemRepositoryInterface
{
    private InvoiceRepository $invoiceRepository;

    public function __construct(ManagerRegistry $registry, InvoiceRepository $invoiceRepository)
    {
        parent::__construct($registry, InventoryItem::class);
        $this->invoiceRepository = $invoiceRepository;
    }

    public function findAll(string $searchTerm = '') : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM inventory_items";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE name LIKE :term OR inn LIKE :term OR supplier LIKE :term OR batch_number LIKE :term";
            $params['term'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY name";

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function findItemsBelowMinStock() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT * 
            FROM inventory_items 
            WHERE quantity < min_stock_level 
            ORDER BY name
        ";
        return $conn->fetchAllAssociative($sql);
    }

    public function countItemsBelowMinStock() : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM inventory_items WHERE quantity < min_stock_level";
        return (int)$conn->fetchOne($sql);
    }

    public function findItemsAboveMaxStock() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT * 
            FROM inventory_items 
            WHERE quantity > max_stock_level 
            ORDER BY name
        ";
        return $conn->fetchAllAssociative($sql);
    }

    public function save(array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $sql = "INSERT INTO inventory_items (name, description, inn, batch_number, expiry_date, 
                                                supplier, cost, quantity, min_stock_level, 
                                                max_stock_level, location) 
                    VALUES (:name, :description, :inn, :batch_number, :expiry_date, 
                            :supplier, :cost, :quantity, :min_stock_level, 
                            :max_stock_level, :location)";

            $success = $conn->executeStatement($sql, [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'inn' => $data['inn'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'cost' => $data['cost'] ?? 0.00,
                'quantity' => $data['quantity'] ?? 0,
                'min_stock_level' => $data['min_stock_level'] ?? 0,
                'max_stock_level' => $data['max_stock_level'] ?? 0,
                'location' => $data['location'] ?? null,
            ]);

            if ($success > 0) {
                $itemId = (int)$conn->lastInsertId();
                if (($data['quantity'] ?? 0) > 0) {
                    $this->logMovement(
                        $itemId,
                        $_SESSION['user']['id'] ?? null,
                        'in',
                        $data['quantity'],
                        $data['quantity'],
                        'Початковий запас',
                        $data['cost'] ?? 0.00
                    );
                }
                $conn->commit();
                return true;
            }
            $conn->rollBack();
            return false;
        } catch (\Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM inventory_items WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $oldItem = $this->findById($id);
            if (!$oldItem) {
                $conn->rollBack();
                return false;
            }
            $oldQuantity = $oldItem['quantity'];
            $newQuantity = $data['quantity'] ?? $oldQuantity;
            $oldCost = $oldItem['cost'];
            $newCost = $data['cost'] ?? $oldCost;

            $sql = "UPDATE inventory_items SET 
                        name = :name, 
                        description = :description, 
                        inn = :inn, 
                        batch_number = :batch_number, 
                        expiry_date = :expiry_date, 
                        supplier = :supplier, 
                        cost = :cost, 
                        quantity = :quantity, 
                        min_stock_level = :min_stock_level, 
                        max_stock_level = :max_stock_level, 
                        location = :location 
                    WHERE id = :id";

            $success = $conn->executeStatement($sql, [
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'inn' => $data['inn'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'cost' => $newCost,
                'quantity' => $newQuantity,
                'min_stock_level' => $data['min_stock_level'] ?? 0,
                'max_stock_level' => $data['max_stock_level'] ?? 0,
                'location' => $data['location'] ?? null,
            ]);

            if ($success > 0) {
                if ($newQuantity !== $oldQuantity) {
                    $movementType = $newQuantity > $oldQuantity ? 'in' : 'out';
                    $quantityChange = abs($newQuantity - $oldQuantity);
                    $reason = $data['movement_reason'] ?? 'Оновлення позиції';
                    $this->logMovement(
                        $id,
                        $_SESSION['user']['id'] ?? null,
                        $movementType,
                        $quantityChange,
                        $newQuantity,
                        $reason,
                        $newCost
                    );
                }
            }
            $conn->commit();
            return $success > 0;
        } catch (\Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    private function logMovement(
        int $itemId,
        ?int $userId,
        string $movementType,
        int $quantityChange,
        int $newQuantity,
        string $reason,
        float $itemCost
    ) : bool {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO inventory_movements (inventory_item_id, user_id, movement_type, 
                                                quantity_change, new_quantity, reason) 
                VALUES (:inventory_item_id, :user_id, :movement_type, 
                        :quantity_change, :new_quantity, :reason)";
        $success = $conn->executeStatement($sql, [
            'inventory_item_id' => $itemId,
            'user_id' => $userId,
            'movement_type' => $movementType,
            'quantity_change' => $quantityChange,
            'new_quantity' => $newQuantity,
            'reason' => $reason,
        ]);

        if ($success > 0) {
            $amount = $quantityChange * $itemCost;
            $transactionType = 'in' === $movementType ? 'inventory_cost' : 'inventory_revenue';
            if ('out' === $movementType) {
                $amount = -$amount;
            }

            $itemDetails = $this->findById($itemId);
            $patientId = 0;
            if (isset($_SESSION['current_patient_id'])) {
                $patientId = $_SESSION['current_patient_id'];
            }

            $this->invoiceRepository->logFinancialTransaction(
                $patientId,
                $amount,
                $transactionType,
                sprintf(
                    "Рух складу: %s %d одиниць '%s'. Причина: %s",
                    'in' === $movementType ? 'Прихід' : 'Вибуття',
                    $quantityChange,
                    $itemDetails['name'],
                    $reason
                ),
                $itemId
            );
        }
        return $success > 0;
    }

    public function getMovementHistory(int $itemId) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                im.*,
                CONCAT(u.last_name, ' ', u.first_name) as user_name
            FROM inventory_movements im
            LEFT JOIN users u ON im.user_id = u.id
            WHERE im.inventory_item_id = :inventory_item_id
            ORDER BY im.created_at DESC
        ";
        return $conn->fetchAllAssociative($sql, ['inventory_item_id' => $itemId]);
    }

    public function findByName(string $name) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM inventory_items WHERE name = :name";
        $result = $conn->fetchAssociative($sql, ['name' => $name]);
        return $result ?: null;
    }

    public function decreaseQuantity(int $itemId, int $quantity, ?int $userId = null, string $reason = 'Виконання рецепту') : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $item = $this->findById($itemId);
            if (!$item || $item['quantity'] < $quantity) {
                $conn->rollBack();
                return false;
            }

            $newQuantity = $item['quantity'] - $quantity;
            $sql = "UPDATE inventory_items SET quantity = :quantity WHERE id = :id";
            $success = $conn->executeStatement($sql, ['quantity' => $newQuantity, 'id' => $itemId]);

            if ($success > 0) {
                $this->logMovement($itemId, $userId, 'out', $quantity, $newQuantity, $reason, $item['cost']);
            }
            $conn->commit();
            return $success > 0;
        } catch (\Exception $e) {
            $conn->rollBack();
            return false;
        }
    }
}
