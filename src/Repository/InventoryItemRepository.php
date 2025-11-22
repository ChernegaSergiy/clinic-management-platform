<?php

namespace App\Repository;

use App\Database;
use PDO;
use App\Repository\InvoiceRepository; // Додано InvoiceRepository

class InventoryItemRepository implements InventoryItemRepositoryInterface
{
    private PDO $pdo;
    private InvoiceRepository $invoiceRepository; // Ініціалізація

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->invoiceRepository = new InvoiceRepository(); // Ініціалізація
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM inventory_items ORDER BY name");
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO inventory_items (name, description, inn, batch_number, expiry_date, supplier, cost, quantity, min_stock_level, max_stock_level, location) 
                    VALUES (:name, :description, :inn, :batch_number, :expiry_date, :supplier, :cost, :quantity, :min_stock_level, :max_stock_level, :location)";
            
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':inn' => $data['inn'] ?? null,
                ':batch_number' => $data['batch_number'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':supplier' => $data['supplier'] ?? null,
                ':cost' => $data['cost'] ?? 0.00,
                ':quantity' => $data['quantity'] ?? 0,
                ':min_stock_level' => $data['min_stock_level'] ?? 0,
                ':max_stock_level' => $data['max_stock_level'] ?? 0,
                ':location' => $data['location'] ?? null,
            ]);

            if ($success) {
                $itemId = (int)$this->pdo->lastInsertId();
                if (($data['quantity'] ?? 0) > 0) {
                    $this->logMovement($itemId, $_SESSION['user']['id'] ?? null, 'in', $data['quantity'], $data['quantity'], 'Початковий запас', $data['cost'] ?? 0.00);
                }
                $this->pdo->commit();
                return true;
            }
            $this->pdo->rollBack();
            return false;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Log error
            return false;
        }
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_items WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function update(int $id, array $data): bool
    {
        $this->pdo->beginTransaction();
        try {
            $oldItem = $this->findById($id);
            if (!$oldItem) {
                $this->pdo->rollBack();
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
            
            $stmt = $this->pdo->prepare($sql);

            $success = $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':inn' => $data['inn'] ?? null,
                ':batch_number' => $data['batch_number'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':supplier' => $data['supplier'] ?? null,
                ':cost' => $newCost,
                ':quantity' => $newQuantity,
                ':min_stock_level' => $data['min_stock_level'] ?? 0,
                ':max_stock_level' => $data['max_stock_level'] ?? 0,
                ':location' => $data['location'] ?? null,
            ]);

            if ($success) {
                if ($newQuantity !== $oldQuantity) {
                    $movementType = $newQuantity > $oldQuantity ? 'in' : 'out';
                    $quantityChange = abs($newQuantity - $oldQuantity);
                    $reason = $data['movement_reason'] ?? 'Оновлення позиції';
                    $this->logMovement($id, $_SESSION['user']['id'] ?? null, $movementType, $quantityChange, $newQuantity, $reason, $newCost);
                }
            }
            $this->pdo->commit();
            return $success;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Log error
            return false;
        }
    }

    private function logMovement(int $itemId, ?int $userId, string $movementType, int $quantityChange, int $newQuantity, string $reason, float $itemCost): bool
    {
        $sql = "INSERT INTO inventory_movements (inventory_item_id, user_id, movement_type, quantity_change, new_quantity, reason) 
                VALUES (:inventory_item_id, :user_id, :movement_type, :quantity_change, :new_quantity, :reason)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':inventory_item_id' => $itemId,
            ':user_id' => $userId,
            ':movement_type' => $movementType,
            ':quantity_change' => $quantityChange,
            ':new_quantity' => $newQuantity,
            ':reason' => $reason,
        ]);

        if ($success) {
            $amount = $quantityChange * $itemCost;
            $transactionType = $movementType === 'in' ? 'inventory_cost' : 'inventory_revenue'; // Assuming 'revenue' for 'out'
            if ($movementType === 'out') $amount = -$amount; // Negative for cost deduction
            
            $itemDetails = $this->findById($itemId);
            $patientId = 0; // Placeholder, as inventory movement might not directly link to a patient
            if (isset($_SESSION['current_patient_id'])) { // If there's a context of a patient
                $patientId = $_SESSION['current_patient_id'];
            }
            
            $this->invoiceRepository->logFinancialTransaction(
                $patientId, // Need a patient ID, might be a system patient or context specific
                $amount,
                $transactionType,
                sprintf("Рух складу: %s %d одиниць '%s'. Причина: %s", $movementType === 'in' ? 'Прихід' : 'Вибуття', $quantityChange, $itemDetails['name'], $reason),
                $itemId // Linked entity ID
            );
        }
        return $success;
    }

    public function getMovementHistory(int $itemId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                im.*,
                CONCAT(u.last_name, ' ', u.first_name) as user_name
            FROM inventory_movements im
            LEFT JOIN users u ON im.user_id = u.id
            WHERE im.inventory_item_id = :inventory_item_id
            ORDER BY im.created_at DESC
        ");
        $stmt->execute([':inventory_item_id' => $itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_items WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function decreaseQuantity(int $itemId, int $quantity, int $userId = null, string $reason = 'Виконання рецепту'): bool
    {
        $this->pdo->beginTransaction();
        try {
            $item = $this->findById($itemId);
            if (!$item || $item['quantity'] < $quantity) {
                $this->pdo->rollBack();
                return false; // Not enough stock or item not found
            }

            $newQuantity = $item['quantity'] - $quantity;
            $sql = "UPDATE inventory_items SET quantity = :quantity WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([':quantity' => $newQuantity, ':id' => $itemId]);

            if ($success) {
                $this->logMovement($itemId, $userId, 'out', $quantity, $newQuantity, $reason, $item['cost']);
            }
            $this->pdo->commit();
            return $success;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Log error
            return false;
        }
    }
}
