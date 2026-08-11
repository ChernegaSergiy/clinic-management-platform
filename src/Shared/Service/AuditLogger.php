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

namespace App\Shared\Service;

use Doctrine\Persistence\ManagerRegistry;

class AuditLogger
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function log(string $entityType, int $entityId, string $action, ?string $oldValue = null, ?string $newValue = null, ?int $userId = null) : bool
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
