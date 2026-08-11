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

class NotificationService
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function createNotification(int $userId, string $message) : bool
    {
        $conn = $this->registry->getConnection();
        $sql = "INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)";
        return $conn->executeStatement($sql, [
            'user_id' => $userId,
            'message' => $message,
        ]) > 0;
    }

    public function markAsRead(int $notificationId) : bool
    {
        $conn = $this->registry->getConnection();
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $notificationId]) > 0;
    }

    public function getNotificationsForUser(int $userId, bool $unreadOnly = false) : array
    {
        $conn = $this->registry->getConnection();
        $sql = "SELECT id, message, is_read, created_at FROM notifications WHERE user_id = :user_id";
        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }
        $sql .= " ORDER BY created_at DESC";
        return $conn->fetchAllAssociative($sql, ['user_id' => $userId]);
    }
}
