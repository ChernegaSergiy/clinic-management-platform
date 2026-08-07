<?php

namespace App\Bundles\NotificationBundle\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Finds the most recent unread notifications for a specific user.
     *
     * @param  int   $userId The ID of the user.
     * @param  int   $limit  The maximum number of notifications to return.
     * @return array An array of unread notifications.
     */
    public function findUnreadByUserId(int $userId, int $limit = 10) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT id, message, created_at
            FROM notifications
            WHERE user_id = :user_id AND is_read = false
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        // Ensure limit is cast to int since PDO handles binds differently for limits.
        // DBAL executes with PDO internally but fetchAllAssociative handles limit well if passed correctly.
        // Alternatively, using standard query.
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function findByUserId(int $userId, int $limit = 10, int $offset = 0) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT id, message, created_at, is_read
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function countUnreadByUserId(int $userId) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = false";
        return (int)$conn->fetchOne($sql, ['user_id' => $userId]);
    }

    public function deleteByIdAndUser(int $id, int $userId) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
        return $conn->executeStatement($sql, [
            'id' => $id,
            'user_id' => $userId,
        ]) > 0;
    }

    /**
     * Marks all unread notifications for a specific user as read.
     *
     * @param  int  $userId The ID of the user.
     * @return bool True on success, false on failure.
     */
    public function markAllAsReadByUserId(int $userId) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE notifications SET is_read = true WHERE user_id = :user_id AND is_read = false";
        return $conn->executeStatement($sql, ['user_id' => $userId]) >= 0;
    }
}
