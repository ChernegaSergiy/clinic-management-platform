<?php

namespace App\Bundles\PrescriptionBundle\DependencyInjection\Compiler;

use App\Bundles\PrescriptionBundle\PrescriptionPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PrescriptionPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['prescription.view.any', 'Перегляд будь-якого рецепту']);
            $registry->addMethodCall('add', ['prescription.view.own', 'Перегляд власних рецептів']);
            $registry->addMethodCall('add', ['prescription.edit.own', 'Редагування власних рецептів']);
            $registry->addMethodCall('add', ['prescription.edit.any', 'Редагування будь-яких рецептів']);
            $registry->addMethodCall('add', ['prescription.create.own', 'Створення власних рецептів']);
            $registry->addMethodCall('add', ['prescription.create.any', 'Створення рецептів від імені будь-якого лікаря']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['prescription.view.any', 'prescription.edit.any', 'prescription.create.any']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['prescription.view.any', 'prescription.edit.any', 'prescription.create.any']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['prescription.view.own', 'prescription.edit.own', 'prescription.create.own']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['prescription.view.own']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['prescription', PrescriptionPolicy::class]);
        }
    }
}
