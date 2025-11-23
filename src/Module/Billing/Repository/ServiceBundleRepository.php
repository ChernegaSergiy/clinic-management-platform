<?php

namespace App\Module\Billing\Repository;

use App\Database;
use PDO;

class ServiceBundleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM service_bundles ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM service_bundles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $bundle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($bundle) {
            $bundle['services'] = $this->getServicesInBundle($id);
        }
        return $bundle === false ? null : $bundle;
    }

    public function save(array $data): ?int
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO service_bundles (name, description, price, is_active) 
                    VALUES (:name, :description, :price, :is_active)";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':price' => $data['price'],
                ':is_active' => $data['is_active'] ?? true,
            ]);
            $bundleId = (int)$this->pdo->lastInsertId();

            if (!empty($data['services']) && is_array($data['services'])) {
                $this->syncServices($bundleId, $data['services']);
            }

            $this->pdo->commit();
            return $bundleId;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Log error
            return null;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE service_bundles SET 
                        name = :name, 
                        description = :description, 
                        price = :price, 
                        is_active = :is_active 
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':price' => $data['price'],
                ':is_active' => $data['is_active'] ?? true,
            ]);

            if ($success && isset($data['services']) && is_array($data['services'])) {
                $this->syncServices($id, $data['services']);
            }

            $this->pdo->commit();
            return $success;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Log error
            return false;
        }
    }

    public function getServicesInBundle(int $bundleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.id, s.name, s.price 
            FROM bundle_services bs
            JOIN services s ON bs.service_id = s.id
            WHERE bs.bundle_id = :bundle_id
            ORDER BY s.name ASC
        ");
        $stmt->execute([':bundle_id' => $bundleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function syncServices(int $bundleId, array $serviceIds): void
    {
        // Remove existing associations
        $deleteSql = "DELETE FROM bundle_services WHERE bundle_id = :bundle_id";
        $deleteStmt = $this->pdo->prepare($deleteSql);
        $deleteStmt->execute([':bundle_id' => $bundleId]);

        if (empty($serviceIds)) {
            return;
        }

        // Add new associations
        $insertSql = "INSERT INTO bundle_services (bundle_id, service_id) VALUES ";
        $values = [];
        $params = [];
        foreach ($serviceIds as $index => $serviceId) {
            $values[] = "(:bundle_id_{$index}, :service_id_{$index})";
            $params[":bundle_id_{$index}"] = $bundleId;
            $params[":service_id_{$index}"] = $serviceId;
        }
        $insertSql .= implode(', ', $values);
        
        $insertStmt = $this->pdo->prepare($insertSql);
        $insertStmt->execute($params);
    }
}
