<?php

namespace App\Module\LabOrder;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LabOrderModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/lab-orders/new', [LabOrderController::class, 'create']);
        $router->add('POST', '/lab-orders/new', [LabOrderController::class, 'store']);
        $router->add('GET', '/lab-orders/show', [LabOrderController::class, 'show']);
        $router->add('GET', '/lab-orders/edit', [LabOrderController::class, 'edit']);
        $router->add('POST', '/lab-orders/edit', [LabOrderController::class, 'update']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\LabOrder\Repository\LabOrderRepository::class)->setPublic(true);
        $container->register(\App\Module\LabOrder\Repository\LabResourceRepository::class)->setPublic(true);
        $container->register(\App\Module\LabOrder\Service\LabImportService::class)->setPublic(true);
        $container->register(\App\Module\LabOrder\LabOrderController::class)
            ->setArguments([
                new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
                new Reference(\App\Module\LabOrder\Repository\LabOrderRepository::class),
                new Reference(\App\Module\User\Repository\UserRepository::class),
                new Reference(\App\Core\Service\NotificationService::class),
                new Reference(\App\Core\Service\QrCodeGenerator::class),
                new Reference(\App\Module\LabOrder\Service\LabImportService::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('lab_order.view.any', 'Перегляд будь-якого лабораторного дослідження');
        $registry->add('lab_order.view.own', 'Перегляд власних лабораторних досліджень');
        $registry->add('lab_order.edit.any', 'Редагування будь-якого лабораторного дослідження');
        $registry->add('lab_order.edit.own', 'Редагування власних лабораторних досліджень');
        $registry->add('lab_order.create', 'Створення лабораторних досліджень');

        $registry->addRoleMapping('admin', ['lab_order.view.any', 'lab_order.edit.any', 'lab_order.create']);
        $registry->addRoleMapping('medical_manager', ['lab_order.view.any']);
        $registry->addRoleMapping('lab_technician', ['lab_order.view.any', 'lab_order.edit.any', 'lab_order.create']);
        $registry->addRoleMapping('doctor', ['lab_order.view.own', 'lab_order.edit.own', 'lab_order.create']);
        $registry->addRoleMapping('nurse', ['lab_order.view.own']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('lab_order', LabOrderPolicy::class);
    }
}
