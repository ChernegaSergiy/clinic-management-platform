<?php

namespace App\Repository;

use App\Database;
use PDO;

class InventoryItemRepository implements InventoryItemRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM inventory_items ORDER BY name");
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO inventory_items (name, description, inn, batch_number, expiry_date, supplier, cost, quantity, min_stock_level, max_stock_level, location) 
                VALUES (:name, :description, :inn, :batch_number, :expiry_date, :supplier, :cost, :quantity, :min_stock_level, :max_stock_level, :location)";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
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

        return $stmt->execute([
            ':id' => $id,
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
    }

    public function findItemsBelowMinStock(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM inventory_items WHERE quantity < min_stock_level AND min_stock_level > 0 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findItemsAboveMaxStock(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM inventory_items WHERE quantity > max_stock_level AND max_stock_level > 0 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
