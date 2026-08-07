<?php

namespace App\Bundles\AppointmentBundle\DependencyInjection\Compiler;

use App\Bundles\AppointmentBundle\AppointmentPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppointmentPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['appointment.view.any', 'Перегляд будь-якого запису']);
            $registry->addMethodCall('add', ['appointment.view.own', 'Перегляд власних записів']);
            $registry->addMethodCall('add', ['appointment.edit.any', 'Редагування будь-якого запису']);
            $registry->addMethodCall('add', ['appointment.edit.own', 'Редагування власних записів']);
            $registry->addMethodCall('add', ['appointment.create', 'Створення записів']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['appointment.view.any', 'appointment.edit.any', 'appointment.create']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['appointment.view.any', 'appointment.edit.any', 'appointment.create']]);
            $registry->addMethodCall('addRoleMapping', ['registrar', ['appointment.view.any', 'appointment.edit.any', 'appointment.create']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['appointment.view.own', 'appointment.edit.own', 'appointment.create']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['appointment.view.own']]);
            $registry->addMethodCall('addRoleMapping', ['billing', ['appointment.view.any']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['appointment', AppointmentPolicy::class]);
        }
    }
}
