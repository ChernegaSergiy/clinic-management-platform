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

namespace App\Bundles\PatientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class PatientExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('patient.features.insurance', $config['features']['insurance']);
        $container->setParameter('patient.features.policies', $config['features']['policies']);
        $container->setParameter('patient.features.export', $config['features']['export']);
    }

    public function prepend(\Symfony\Component\DependencyInjection\ContainerBuilder $container) : void
    {
        $roleHierarchy = [
            'ROLE_ADMIN' => [
                'ROLE_PATIENT_VIEW_ANY',
                'ROLE_PATIENT_EDIT_ANY',
                'ROLE_PATIENT_CREATE',
            ],
            'ROLE_MEDICAL_MANAGER' => [
                'ROLE_PATIENT_VIEW_ANY',
            ],
            'ROLE_REGISTRAR' => [
                'ROLE_PATIENT_VIEW_ANY',
                'ROLE_PATIENT_EDIT_ANY',
                'ROLE_PATIENT_CREATE',
            ],
            'ROLE_DOCTOR' => [
                'ROLE_PATIENT_VIEW_OWN',
                'ROLE_PATIENT_EDIT_OWN',
            ],
            'ROLE_NURSE' => [
                'ROLE_PATIENT_VIEW_OWN',
            ],
        ];

        $container->prependExtensionConfig('security', [
            'role_hierarchy' => $roleHierarchy,
        ]);
    }
}
