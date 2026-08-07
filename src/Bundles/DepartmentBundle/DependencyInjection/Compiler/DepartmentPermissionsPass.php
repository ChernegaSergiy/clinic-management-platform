<?php

namespace App\Bundles\DepartmentBundle\DependencyInjection\Compiler;

use App\Bundles\DepartmentBundle\DepartmentPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DepartmentPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['department.read', 'Перегляд відділень']);
            $registry->addMethodCall('add', ['department.write', 'Редагування відділень']);
            $registry->addMethodCall('add', ['department.delete', 'Видалення відділень']);
            $registry->addMethodCall('add', ['department.manage', 'Керування відділеннями']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['department.read', 'department.write', 'department.delete', 'department.manage']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['department.read', 'department.write']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['department', DepartmentPolicy::class]);
        }
    }
}
