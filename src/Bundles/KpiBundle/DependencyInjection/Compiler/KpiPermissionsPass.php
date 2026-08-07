<?php

namespace App\Bundles\KpiBundle\DependencyInjection\Compiler;

use App\Bundles\KpiBundle\KpiPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KpiPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['kpi.read', 'Перегляд KPI']);
            $registry->addMethodCall('add', ['kpi.manage', 'Керування KPI']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['kpi.read', 'kpi.manage']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['kpi.read']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['kpi', KpiPolicy::class]);
        }
    }
}
