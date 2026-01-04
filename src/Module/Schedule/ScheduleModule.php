<?php

namespace App\Module\Schedule;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\Schedule\ScheduleController;

class ScheduleModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/doctor/schedule', [ScheduleController::class, 'index']);
        $router->add('POST', '/doctor/schedule/update', [ScheduleController::class, 'update']);
        $router->add('POST', '/doctor/schedule/exceptions/add', [ScheduleController::class, 'addException']);
        $router->add('POST', '/doctor/schedule/exceptions/delete', [ScheduleController::class, 'deleteException']);

        $router->add('GET', '/admin/schedules', [ScheduleController::class, 'adminIndex']);
        $router->add('GET', '/admin/schedules/show', [ScheduleController::class, 'adminShow']);
        $router->add('GET', '/admin/schedules/edit', [ScheduleController::class, 'adminEdit']);
        $router->add('POST', '/admin/schedules/update', [ScheduleController::class, 'adminUpdate']);
        $router->add('POST', '/admin/schedules/exceptions/add', [ScheduleController::class, 'adminAddException']);
        $router->add('POST', '/admin/schedules/exceptions/delete', [ScheduleController::class, 'adminDeleteException']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('schedules.manage', 'Керування розкладами');

        $registry->addRoleMapping('admin', ['schedules.manage']);
        $registry->addRoleMapping('doctor', ['schedules.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }
}