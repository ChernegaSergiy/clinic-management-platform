<?php

namespace App\Module\Hrm;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\Hrm\HrmController;

class HrmModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/hrm', [HrmController::class, 'index']);
        $router->add('GET', '/hrm/new', [HrmController::class, 'create']);
        $router->add('POST', '/hrm/new', [HrmController::class, 'store']);
        $router->add('GET', '/hrm/show', [HrmController::class, 'show']);
        $router->add('GET', '/hrm/edit', [HrmController::class, 'edit']);
        $router->add('POST', '/hrm/edit', [HrmController::class, 'update']);
        $router->add('POST', '/hrm/toggle-status', [HrmController::class, 'toggleStatus']);
    }
}