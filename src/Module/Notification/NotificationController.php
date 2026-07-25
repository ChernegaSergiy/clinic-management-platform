<?php

namespace App\Module\Notification;

use App\Core\Auth\Gate;
use App\Module\Notification\Repository\NotificationRepository;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    #[Route('/notifications', name: 'notifications_unread', methods: ['GET'])]
    public function getUnread(): JsonResponse
    {
        $this->checkAuth();
        Gate::authorize('notifications.read');
        $userId = (int)($_SESSION['user']['id'] ?? 0);

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
    public function markAllRead(): JsonResponse
    {
        $this->checkAuth();
        Gate::authorize('notifications.read');
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $success = $this->notificationRepository->markAllAsReadByUserId($userId);

        return new JsonResponse(['success' => $success]);
    }

    /**
     * API endpoint to delete a notification for the logged-in user.
     */
    #[Route('/notifications/delete', name: 'notifications_delete', methods: ['POST'])]
    public function delete(): JsonResponse
    {
        $this->checkAuth();
        Gate::authorize('notifications.read');
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $id = (int)($_POST['id'] ?? 0);
        $success = $this->notificationRepository->deleteByIdAndUser($id, $userId);

        return new JsonResponse(['success' => $success]);
    }
}
