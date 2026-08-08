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

namespace App\Bundles\LabOrderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class LabOrderExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container) : void {}

    public function prepend(\Symfony\Component\DependencyInjection\ContainerBuilder $container) : void
    {
        $roleHierarchy = [
            'ROLE_ADMIN' => [
                'ROLE_LAB_ORDER_VIEW_ANY',
                'ROLE_LAB_ORDER_EDIT_ANY',
                'ROLE_LAB_ORDER_CREATE',
            ],
            'ROLE_MEDICAL_MANAGER' => [
                'ROLE_LAB_ORDER_VIEW_ANY',
            ],
            'ROLE_LAB_TECHNICIAN' => [
                'ROLE_LAB_ORDER_VIEW_ANY',
                'ROLE_LAB_ORDER_EDIT_ANY',
                'ROLE_LAB_ORDER_CREATE',
            ],
            'ROLE_DOCTOR' => [
                'ROLE_LAB_ORDER_VIEW_OWN',
                'ROLE_LAB_ORDER_EDIT_OWN',
                'ROLE_LAB_ORDER_CREATE',
            ],
            'ROLE_NURSE' => [
                'ROLE_LAB_ORDER_VIEW_OWN',
            ],
        ];

        $container->prependExtensionConfig('security', [
            'role_hierarchy' => $roleHierarchy,
        ]);
    }
}
