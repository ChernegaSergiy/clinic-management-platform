<?php

namespace App\Module\Room;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\Room\RoomController;

class RoomModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/admin/rooms', [RoomController::class, 'index']);
        $router->add('GET', '/admin/rooms/new', [RoomController::class, 'create']);
        $router->add('POST', '/admin/rooms/new', [RoomController::class, 'store']);
        $router->add('GET', '/admin/rooms/show', [RoomController::class, 'show']);
        $router->add('GET', '/admin/rooms/edit', [RoomController::class, 'edit']);
        $router->add('POST', '/admin/rooms/edit', [RoomController::class, 'update']);
        $router->add('POST', '/admin/rooms/delete', [RoomController::class, 'delete']);
        $router->add('GET', '/api/calendar/rooms', [RoomController::class, 'apiRooms']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('rooms.manage', 'Керування приміщеннями');

        $registry->addRoleMapping('admin', ['rooms.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('rooms', RoomPolicy::class);
    }
}
