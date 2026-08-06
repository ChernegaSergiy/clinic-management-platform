<?php

namespace App\Module\Department;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DepartmentModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/admin/departments', [DepartmentController::class, 'index']);
        $router->add('GET', '/admin/departments/new', [DepartmentController::class, 'create']);
        $router->add('POST', '/admin/departments/new', [DepartmentController::class, 'store']);
        $router->add('GET', '/admin/departments/show', [DepartmentController::class, 'show']);
        $router->add('GET', '/admin/departments/edit', [DepartmentController::class, 'edit']);
        $router->add('POST', '/admin/departments/edit', [DepartmentController::class, 'update']);
        $router->add('POST', '/admin/departments/delete', [DepartmentController::class, 'delete']);
        $router->add('POST', '/admin/departments/toggle-status', [DepartmentController::class, 'toggleStatus']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Department\Repository\DepartmentRepository::class)->setPublic(true);
        $container->register(\App\Module\Department\DepartmentController::class)
            ->setArguments([
                new Reference(\App\Module\Department\Repository\DepartmentRepository::class),
                new Reference(\App\Module\Hrm\Repository\HrmRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('department.read', 'Перегляд відділень');
        $registry->add('department.write', 'Редагування відділень');
        $registry->add('department.delete', 'Видалення відділень');
        $registry->add('department.manage', 'Керування відділеннями');

        $registry->addRoleMapping('admin', ['department.read', 'department.write', 'department.delete', 'department.manage']);
        $registry->addRoleMapping('medical_manager', ['department.read', 'department.write']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('department', DepartmentPolicy::class);
    }
}
