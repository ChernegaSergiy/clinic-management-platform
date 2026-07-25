<?php

namespace App\Module\Notification;

use App\Core\Auth\Gate;
use App\Module\Notification\Repository\NotificationRepository;

class NotificationController extends \App\Core\Controller\AbstractController
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
    public function getUnread(): void
    {
        $this->checkAuth();
        Gate::authorize('notifications.read');
        $userId = (int)($_SESSION['user']['id'] ?? 0); // userId is guaranteed to be set if $this->checkAuth() passes

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        // Fetch one extra to know if more pages exist
        $notifications = $this->notificationRepository->findByUserId($userId, $limit + 1, $offset);
        $hasMore = count($notifications) > $limit;
        if ($hasMore) {
            array_pop($notifications);
        }

        $unreadCount = $this->notificationRepository->countUnreadByUserId($userId);

        header('Content-Type: application/json');
        echo json_encode([
            'notifications' => $notifications,
            'has_more' => $hasMore,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * API endpoint to mark all notifications for the logged-in user as read.
     */
    public function markAllRead(): void
    {
        $this->checkAuth();
        Gate::authorize('notifications.read');
        $userId = (int)($_SESSION['user']['id'] ?? 0); // userId is guaranteed to be set if $this->checkAuth() passes

        $success = $this->notificationRepository->markAllAsReadByUserId($userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    /**
     * API endpoint to delete a notification for the logged-in user.
     */
    public function delete(): void
    {
        $this->checkAuth();
        Gate::authorize('notifications.read');
        $userId = (int)($_SESSION['user']['id'] ?? 0); // userId is guaranteed to be set if $this->checkAuth() passes

        $id = (int)($_POST['id'] ?? 0);
        $success = $this->notificationRepository->deleteByIdAndUser($id, $userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
}
