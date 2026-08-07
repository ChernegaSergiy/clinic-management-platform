<?php

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
