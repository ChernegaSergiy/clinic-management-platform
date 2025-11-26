<?php

namespace App\Module\Notification\Repository;

use App\Database;
use PDO;

class NotificationRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Finds the most recent unread notifications for a specific user.
     *
     * @param int $userId The ID of the user.
     * @param int $limit The maximum number of notifications to return.
     * @return array An array of unread notifications.
     */
    public function findUnreadByUserId(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, message, created_at
            FROM notifications
            WHERE user_id = :user_id AND is_read = false
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByUserId(int $userId, int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, message, created_at, is_read
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnreadByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = false
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marks all unread notifications for a specific user as read.
     *
     * @param int $userId The ID of the user.
     * @return bool True on success, false on failure.
     */
    public function markAllAsReadByUserId(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE notifications
            SET is_read = true
            WHERE user_id = :user_id AND is_read = false
        ");
        return $stmt->execute([':user_id' => $userId]);
    }
}
