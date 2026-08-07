<?php

namespace App\Bundles\HrmBundle\DependencyInjection\Compiler;

use App\Bundles\HrmBundle\HrmPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HrmPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['hrm.read', 'Перегляд співробітників']);
            $registry->addMethodCall('add', ['hrm.write', 'Редагування співробітників']);
            $registry->addMethodCall('add', ['hrm.manage', 'Керування співробітниками']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['hrm.read', 'hrm.write', 'hrm.manage']]);
            $registry->addMethodCall('addRoleMapping', ['hr_manager', ['hrm.read', 'hrm.write', 'hrm.manage']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['hrm.read']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['hrm', HrmPolicy::class]);
        }
    }
}
