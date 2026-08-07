<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

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
