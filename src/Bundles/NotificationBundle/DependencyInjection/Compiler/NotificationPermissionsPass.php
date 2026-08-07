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

namespace App\Bundles\NotificationBundle\DependencyInjection\Compiler;

use App\Core\Auth\PermissionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NotificationPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['notifications.read', 'Перегляд сповіщень']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['registrar', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['lab_technician', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['billing', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['inventory_manager', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['hr_manager', ['notifications.read']]);
        }
    }
}
