<?php

namespace App\Core;

use App\Database;
use PDO;

class AuditLogger
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function log(string $entityType, int $entityId, string $action, ?string $oldValue = null, ?string $newValue = null, ?int $userId = null): bool
    {
        $sql = "INSERT INTO audit_logs (entity_type, entity_id, user_id, action, old_value, new_value) 
                VALUES (:entity_type, :entity_id, :user_id, :action, :old_value, :new_value)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':user_id' => $userId, // This will need to be dynamically provided later
            ':action' => $action,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
        ]);
    }
}
