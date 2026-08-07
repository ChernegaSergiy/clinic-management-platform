<?php

namespace App\Bundles\MedicalRecordBundle\DependencyInjection\Compiler;

use App\Bundles\MedicalRecordBundle\MedicalRecordPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MedicalRecordPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['medical_record.view.any', 'Перегляд будь-якого медичного запису']);
            $registry->addMethodCall('add', ['medical_record.view.own', 'Перегляд власних медичних записів']);
            $registry->addMethodCall('add', ['medical_record.edit.own', 'Редагування власних медичних записів']);
            $registry->addMethodCall('add', ['medical_record.edit.any', 'Редагування будь-яких медичних записів']);
            $registry->addMethodCall('add', ['medical_record.create', 'Створення медичних записів']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['medical_record.view.any', 'medical_record.edit.any', 'medical_record.create']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['medical_record.view.any', 'medical_record.edit.any']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['medical_record.view.own', 'medical_record.edit.own', 'medical_record.create']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['medical_record.view.own']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['medical_record', MedicalRecordPolicy::class]);
        }
    }
}
