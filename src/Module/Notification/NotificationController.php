<?php

namespace App\Module\Notification;

use App\Core\AuthGuard;
use App\Module\Notification\Repository\NotificationRepository;

class NotificationController
{
    private NotificationRepository $notificationRepository;

    public function __construct()
    {
        $this->notificationRepository = new NotificationRepository();
    }

    /**
     * API endpoint to get unread notifications for the logged-in user.
     */
    public function getUnread(): void
    {
        AuthGuard::check();
        $userId = $_SESSION['user']['id'] ?? 0;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $notifications = $this->notificationRepository->findUnreadByUserId($userId);

        header('Content-Type: application/json');
        echo json_encode($notifications);
    }

    /**
     * API endpoint to mark all notifications for the logged-in user as read.
     */
    public function markAllRead(): void
    {
        AuthGuard::check();
        $userId = $_SESSION['user']['id'] ?? 0;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $success = $this->notificationRepository->markAllAsReadByUserId($userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
}
