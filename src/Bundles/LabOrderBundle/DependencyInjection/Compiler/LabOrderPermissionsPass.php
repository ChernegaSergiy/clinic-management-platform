<?php

namespace App\Bundles\LabOrderBundle\DependencyInjection\Compiler;

use App\Bundles\LabOrderBundle\LabOrderPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LabOrderPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['lab_order.view.any', 'Перегляд будь-якого лабораторного дослідження']);
            $registry->addMethodCall('add', ['lab_order.view.own', 'Перегляд власних лабораторних досліджень']);
            $registry->addMethodCall('add', ['lab_order.edit.any', 'Редагування будь-якого лабораторного дослідження']);
            $registry->addMethodCall('add', ['lab_order.edit.own', 'Редагування власних лабораторних досліджень']);
            $registry->addMethodCall('add', ['lab_order.create', 'Створення лабораторних досліджень']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['lab_order.view.any', 'lab_order.edit.any', 'lab_order.create']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['lab_order.view.any']]);
            $registry->addMethodCall('addRoleMapping', ['lab_technician', ['lab_order.view.any', 'lab_order.edit.any', 'lab_order.create']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['lab_order.view.own', 'lab_order.edit.own', 'lab_order.create']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['lab_order.view.own']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['lab_order', LabOrderPolicy::class]);
        }
    }
}
