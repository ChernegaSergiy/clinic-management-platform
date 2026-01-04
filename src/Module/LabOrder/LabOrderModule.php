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


    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('lab.read', 'Перегляд лабораторних досліджень');
        $registry->add('lab.write', 'Редагування лабораторних досліджень');

        $registry->addRoleMapping('admin', ['lab.read', 'lab.write']);
        $registry->addRoleMapping('medical_manager', ['lab.read']);
        $registry->addRoleMapping('doctor', ['lab.read', 'lab.write']);
        $registry->addRoleMapping('nurse', ['lab.read', 'lab.write']);
        $registry->addRoleMapping('lab_technician', ['lab.read', 'lab.write']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('lab_order', LabOrderPolicy::class);
    }
}
