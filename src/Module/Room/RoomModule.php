<?php

namespace App\Module\Room;

use App\Core\BaseModule;
use App\Core\Router;
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
}