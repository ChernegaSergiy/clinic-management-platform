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
