<?php

namespace App\Module\Hrm;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\Hrm\HrmController;

class HrmModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/dashboard/hrm', [HrmController::class, 'index']);
        $router->add('GET', '/dashboard/hrm/new', [HrmController::class, 'create']);
        $router->add('POST', '/dashboard/hrm/new', [HrmController::class, 'store']);
        $router->add('GET', '/dashboard/hrm/show', [HrmController::class, 'show']);
        $router->add('GET', '/dashboard/hrm/edit', [HrmController::class, 'edit']);
        $router->add('POST', '/dashboard/hrm/edit', [HrmController::class, 'update']);
        $router->add('POST', '/dashboard/hrm/toggle-status', [HrmController::class, 'toggleStatus']);
    }
}