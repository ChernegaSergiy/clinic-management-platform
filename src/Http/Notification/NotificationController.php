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

namespace App\Http\Notification;

use App\Domain\Notification\NotificationRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class NotificationController extends AbstractController
{
    private NotificationRepository $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * API endpoint to get notifications (read + unread) with pagination.
     * Query params: page (1-based), limit.
     */
    #[Route('/notifications', name: 'notifications_unread', methods: ['GET'])]
    public function getUnread(#[CurrentUser] User $user) : JsonResponse
    {
        $this->denyAccessUnlessGranted('NOTIFICATION_VIEW');
        $userId = $user->getId();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $notifications = $this->notificationRepository->findByUserId($userId, $limit + 1, $offset);
        $hasMore = count($notifications) > $limit;
        if ($hasMore) {
            array_pop($notifications);
        }

        $unreadCount = $this->notificationRepository->countUnreadByUserId($userId);

        return new JsonResponse([
            'notifications' => $notifications,
            'has_more' => $hasMore,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * API endpoint to mark all notifications for the logged-in user as read.
     */
    #[Route('/notifications/mark-all-read', name: 'notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(#[CurrentUser] User $user) : JsonResponse
    {
        $this->denyAccessUnlessGranted('NOTIFICATION_VIEW');
        $userId = $user->getId();

        $success = $this->notificationRepository->markAllAsReadByUserId($userId);

        return new JsonResponse(['success' => $success]);
    }

    /**
     * API endpoint to delete a notification for the logged-in user.
     */
    #[Route('/notifications/delete', name: 'notifications_delete', methods: ['POST'])]
    public function delete(#[CurrentUser] User $user) : JsonResponse
    {
        $this->denyAccessUnlessGranted('NOTIFICATION_VIEW');
        $userId = $user->getId();

        $id = (int)($_POST['id'] ?? 0);
        $success = $this->notificationRepository->deleteByIdAndUser($id, $userId);

        return new JsonResponse(['success' => $success]);
    }
}
