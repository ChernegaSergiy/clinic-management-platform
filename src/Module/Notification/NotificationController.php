<?php

namespace App\Module\Notification;

use App\Core\AuthGuard;
use App\Module\Notification\Repository\NotificationRepository;
use App\Core\Gate;

class NotificationController
{
    private NotificationRepository $notificationRepository;

    public function __construct()
    {
        $this->notificationRepository = new NotificationRepository();
    }

    /**
     * API endpoint to get notifications (read + unread) with pagination.
     * Query params: page (1-based), limit.
     */
    public function getUnread(): void
    {
        AuthGuard::check();
        Gate::authorize('notifications.read');
        $userId = $_SESSION['user']['id'] ?? 0;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

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
        AuthGuard::check();
        Gate::authorize('notifications.read');
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

    /**
     * API endpoint to delete a notification for the logged-in user.
     */
    public function delete(): void
    {
        AuthGuard::check();
        Gate::authorize('notifications.read');
        $userId = $_SESSION['user']['id'] ?? 0;
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
        $id = (int)($input['id'] ?? ($_POST['id'] ?? 0));

        if (!$userId || $id === 0) {
            http_response_code(400);
            echo json_encode(['success' => false]);
            return;
        }

        $success = $this->notificationRepository->deleteByIdAndUser($id, $userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
}
