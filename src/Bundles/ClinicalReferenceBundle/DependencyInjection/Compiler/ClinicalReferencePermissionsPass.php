<?php

namespace App\Bundles\ClinicalReferenceBundle\DependencyInjection\Compiler;

use App\Bundles\ClinicalReferenceBundle\ClinicalReferencePolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClinicalReferencePermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['clinical.manage', 'Керування клінічними довідниками']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['clinical.manage']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['clinical.manage']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['clinical', ClinicalReferencePolicy::class]);
        }
    }
}
