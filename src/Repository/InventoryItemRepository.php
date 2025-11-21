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
}
