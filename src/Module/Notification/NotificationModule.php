<?php

namespace App\Module\Notification;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class NotificationModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/api/notifications', [NotificationController::class, 'getUnread']);
        $router->add('POST', '/api/notifications/mark-read', [NotificationController::class, 'markAllRead']);
        $router->add('POST', '/api/notifications/delete', [NotificationController::class, 'delete']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Notification\Repository\NotificationRepository::class)->setPublic(true);
        $container->register(\App\Module\Notification\NotificationController::class)
            ->setArguments([
                new Reference(\App\Module\Notification\Repository\NotificationRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('notifications.read', 'Перегляд сповіщень');

        $registry->addRoleMapping('admin', ['notifications.read']);
        $registry->addRoleMapping('medical_manager', ['notifications.read']);
        $registry->addRoleMapping('registrar', ['notifications.read']);
        $registry->addRoleMapping('doctor', ['notifications.read']);
        $registry->addRoleMapping('nurse', ['notifications.read']);
        $registry->addRoleMapping('lab_technician', ['notifications.read']);
        $registry->addRoleMapping('billing', ['notifications.read']);
        $registry->addRoleMapping('inventory_manager', ['notifications.read']);
        $registry->addRoleMapping('hr_manager', ['notifications.read']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void {}
}
