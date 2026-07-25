<?php

namespace App\Core\Service;

use Doctrine\Persistence\ManagerRegistry;

class NotificationService
{
    private ManagerRegistry $registry;
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function createNotification(int $userId, string $message): bool
    {
        $conn = $this->registry->getConnection();
        $sql = "INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)";
        return $conn->executeStatement($sql, [
            'user_id' => $userId,
            'message' => $message,
        ]) > 0;
    }

    public function markAsRead(int $notificationId): bool
    {
        $conn = $this->registry->getConnection();
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $notificationId]) > 0;
    }

    public function getNotificationsForUser(int $userId, bool $unreadOnly = false): array
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
