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

namespace App\Bundles\HrmBundle\DependencyInjection\Compiler;

use App\Bundles\HrmBundle\HrmPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HrmPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['hrm.read', 'Перегляд співробітників']);
            $registry->addMethodCall('add', ['hrm.write', 'Редагування співробітників']);
            $registry->addMethodCall('add', ['hrm.manage', 'Керування співробітниками']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['hrm.read', 'hrm.write', 'hrm.manage']]);
            $registry->addMethodCall('addRoleMapping', ['hr_manager', ['hrm.read', 'hrm.write', 'hrm.manage']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['hrm.read']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['hrm', HrmPolicy::class]);
        }
    }
}
