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
