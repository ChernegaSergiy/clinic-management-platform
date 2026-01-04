<?php

namespace App\Module\LabOrder;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\LabOrder\LabOrderController;

class LabOrderModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/lab-orders/new', [LabOrderController::class, 'create']);
        $router->add('POST', '/lab-orders/new', [LabOrderController::class, 'store']);
        $router->add('GET', '/lab-orders/show', [LabOrderController::class, 'show']);
        $router->add('GET', '/lab-orders/edit', [LabOrderController::class, 'edit']);
        $router->add('POST', '/lab-orders/edit', [LabOrderController::class, 'update']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('lab_order', LabOrderPolicy::class);
    }
}

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }
}