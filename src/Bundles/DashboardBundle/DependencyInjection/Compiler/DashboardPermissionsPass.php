<?php

namespace App\Bundles\DashboardBundle\DependencyInjection\Compiler;

use App\Bundles\DashboardBundle\DashboardPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DashboardPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);

            $registry->addMethodCall('add', ['dashboard.view', 'Перегляд панелі']);
            $registry->addMethodCall('add', ['dashboard.export', 'Експорт даних']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['dashboard.view', 'dashboard.export']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['dashboard.view', 'dashboard.export']]);
            $registry->addMethodCall('addRoleMapping', ['registrar', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['lab_technician', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['billing', ['dashboard.view', 'dashboard.export']]);
            $registry->addMethodCall('addRoleMapping', ['inventory_manager', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['hr_manager', ['dashboard.view']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['dashboard', DashboardPolicy::class]);
        }
    }
}
