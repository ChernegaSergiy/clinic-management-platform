<?php

namespace App\Module\Notification;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\Notification\NotificationController;

class NotificationModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/api/notifications', [NotificationController::class, 'getUnread']);
        $router->add('POST', '/api/notifications/mark-read', [NotificationController::class, 'markAllRead']);
        $router->add('POST', '/api/notifications/delete', [NotificationController::class, 'delete']);
    }
}