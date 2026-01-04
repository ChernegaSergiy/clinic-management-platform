<?php

namespace App\Module\Hrm;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
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

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('hrm.read', 'Перегляд співробітників');
        $registry->add('hrm.write', 'Редагування співробітників');
        $registry->add('hrm.manage', 'Керування співробітниками');

        $registry->addRoleMapping('admin', ['hrm.read', 'hrm.write', 'hrm.manage']);
        $registry->addRoleMapping('hr_manager', ['hrm.read', 'hrm.write', 'hrm.manage']);
        $registry->addRoleMapping('medical_manager', ['hrm.read']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('hrm', HrmPolicy::class);
    }
}
