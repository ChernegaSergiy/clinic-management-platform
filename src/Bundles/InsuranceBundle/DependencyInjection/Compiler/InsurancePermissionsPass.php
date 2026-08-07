<?php

namespace App\Bundles\InsuranceBundle\DependencyInjection\Compiler;

use App\Bundles\InsuranceBundle\InsurancePolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InsurancePermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['insurance.manage', 'Керування страховкою']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['insurance.manage']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['insurance', InsurancePolicy::class]);
        }
    }
}
