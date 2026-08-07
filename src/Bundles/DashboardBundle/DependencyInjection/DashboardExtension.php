<?php

namespace App\Bundles\DashboardBundle\DependencyInjection;

use App\Bundles\DashboardBundle\DashboardPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class DashboardExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $this->registerPermissions($container);
        $this->registerPolicies($container);

        $container->setParameter('dashboard.features.export', $config['features']['export']);
    }

    private function registerPermissions(ContainerBuilder $container) : void
    {
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

    private function registerPolicies(ContainerBuilder $container) : void
    {
        $registry = $container->getDefinition(PolicyRegistry::class);
        $registry->addMethodCall('register', ['dashboard', DashboardPolicy::class]);
    }
}
