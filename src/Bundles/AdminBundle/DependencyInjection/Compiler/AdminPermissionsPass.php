<?php

namespace App\Bundles\AdminBundle\DependencyInjection\Compiler;

use App\Core\Auth\PermissionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AdminPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['admin.manage', 'Керування адмінпанеллю']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['admin.manage']]);
        }
    }
}
