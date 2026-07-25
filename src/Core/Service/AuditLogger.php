<?php

namespace App\Core\Service;

use Doctrine\Persistence\ManagerRegistry;

class AuditLogger
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function log(string $entityType, int $entityId, string $action, ?string $oldValue = null, ?string $newValue = null, ?int $userId = null): bool
    {
        $conn = $this->registry->getConnection();
        
        $sql = "INSERT INTO audit_logs (entity_type, entity_id, user_id, action, old_value, new_value) 
                VALUES (:entity_type, :entity_id, :user_id, :action, :old_value, :new_value)";

        return $conn->executeStatement($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]) > 0;
    }
}
