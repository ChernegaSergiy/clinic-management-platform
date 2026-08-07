<?php

namespace App\Bundles\BillingBundle\DependencyInjection\Compiler;

use App\Bundles\BillingBundle\BillingPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BillingPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['billing.read', 'Перегляд рахунків']);
            $registry->addMethodCall('add', ['billing.manage', 'Керування рахунками']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['billing.read', 'billing.manage']]);
            $registry->addMethodCall('addRoleMapping', ['billing', ['billing.read', 'billing.manage']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['billing', BillingPolicy::class]);
        }
    }
}
