<?php

namespace App\Bundles\InventoryBundle\DependencyInjection\Compiler;

use App\Bundles\InventoryBundle\InventoryPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InventoryPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['inventory.manage', 'Керування складом']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['inventory.manage']]);
            $registry->addMethodCall('addRoleMapping', ['inventory_manager', ['inventory.manage']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['inventory', InventoryPolicy::class]);
        }
    }
}
