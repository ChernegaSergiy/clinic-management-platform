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

namespace App\Bundles\PatientBundle\DependencyInjection\Compiler;

use App\Bundles\PatientBundle\PatientPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PatientPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['patient.view.any', 'Перегляд будь-якого пацієнта']);
            $registry->addMethodCall('add', ['patient.view.own', 'Перегляд призначених пацієнтів']);
            $registry->addMethodCall('add', ['patient.edit.any', 'Редагування будь-якого пацієнта']);
            $registry->addMethodCall('add', ['patient.edit.own', 'Редагування призначених пацієнтів']);
            $registry->addMethodCall('add', ['patient.create', 'Створення пацієнтів']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['patient.view.any', 'patient.edit.any', 'patient.create']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['patient.view.any']]);
            $registry->addMethodCall('addRoleMapping', ['registrar', ['patient.view.any', 'patient.edit.any', 'patient.create']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['patient.view.own', 'patient.edit.own']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['patient.view.own']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['patient', PatientPolicy::class]);
        }
    }
}
