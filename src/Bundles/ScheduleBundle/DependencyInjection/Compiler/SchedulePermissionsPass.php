<?php

namespace App\Bundles\ScheduleBundle\DependencyInjection\Compiler;

use App\Bundles\ScheduleBundle\SchedulePolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SchedulePermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['schedules.manage_all', 'Керування всіма розкладами']);
            $registry->addMethodCall('add', ['schedules.manage_own', 'Керування власним розкладом']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['schedules.manage_all']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['schedules.manage_all']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['schedules.manage_own']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['schedules', SchedulePolicy::class]);
        }
    }
}
