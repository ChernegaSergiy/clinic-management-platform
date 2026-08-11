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

namespace App\Domain\Notification;

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
        $qb = $this->createQueryBuilder('n')
            ->select('n.id', 'n.message', 'n.created_at')
            ->where('n.user_id = :user_id')
            ->andWhere('n.is_read = false')
            ->setParameter('user_id', $userId)
            ->orderBy('n.created_at', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    public function findByUserId(int $userId, int $limit = 10, int $offset = 0) : array
    {
        $qb = $this->createQueryBuilder('n')
            ->select('n.id', 'n.message', 'n.created_at', 'n.is_read')
            ->where('n.user_id = :user_id')
            ->setParameter('user_id', $userId)
            ->orderBy('n.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getArrayResult();
    }

    public function countUnreadByUserId(int $userId) : int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user_id = :user_id')
            ->andWhere('n.is_read = false')
            ->setParameter('user_id', $userId);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function deleteByIdAndUser(int $id, int $userId) : bool
    {
        $qb = $this->createQueryBuilder('n')
            ->delete()
            ->where('n.id = :id')
            ->andWhere('n.user_id = :user_id')
            ->setParameter('id', $id)
            ->setParameter('user_id', $userId);

        return $qb->getQuery()->execute() > 0;
    }

    /**
     * Marks all unread notifications for a specific user as read.
     *
     * @param  int  $userId The ID of the user.
     * @return bool True on success, false on failure.
     */
    public function markAllAsReadByUserId(int $userId) : bool
    {
        $qb = $this->createQueryBuilder('n')
            ->update()
            ->set('n.is_read', 'true')
            ->where('n.user_id = :user_id')
            ->andWhere('n.is_read = false')
            ->setParameter('user_id', $userId);

        return $qb->getQuery()->execute() >= 0;
    }
}
