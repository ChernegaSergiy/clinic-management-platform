<?php

namespace App\Core;

use App\Database;
use PDO;

class NotificationService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function createNotification(int $userId, string $message): bool
    {
        $sql = "INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':message' => $message,
        ]);
    }

    public function markAsRead(int $notificationId): bool
    {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $notificationId]);
    }

    public function getNotificationsForUser(int $userId, bool $unreadOnly = false): array
    {
        $sql = "SELECT id, message, is_read, created_at FROM notifications WHERE user_id = :user_id";
        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}