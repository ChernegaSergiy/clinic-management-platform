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

namespace App\Bundles\DashboardBundle\DependencyInjection\Compiler;

use App\Bundles\DashboardBundle\DashboardPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DashboardPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);

            $registry->addMethodCall('add', ['dashboard.view', 'Перегляд панелі']);
            $registry->addMethodCall('add', ['dashboard.export', 'Експорт даних']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['dashboard.view', 'dashboard.export']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['dashboard.view', 'dashboard.export']]);
            $registry->addMethodCall('addRoleMapping', ['registrar', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['lab_technician', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['billing', ['dashboard.view', 'dashboard.export']]);
            $registry->addMethodCall('addRoleMapping', ['inventory_manager', ['dashboard.view']]);
            $registry->addMethodCall('addRoleMapping', ['hr_manager', ['dashboard.view']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['dashboard', DashboardPolicy::class]);
        }
    }
}
