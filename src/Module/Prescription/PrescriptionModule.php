<?php

namespace App\Module\Prescription;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PrescriptionModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/prescriptions', [PrescriptionController::class, 'index']);
        $router->add('GET', '/prescriptions/new', [PrescriptionController::class, 'create']);
        $router->add('POST', '/prescriptions/new', [PrescriptionController::class, 'store']);
        $router->add('GET', '/prescriptions/show', [PrescriptionController::class, 'show']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Prescription\Repository\PrescriptionRepository::class)->setPublic(true);
        $container->register(\App\Module\Prescription\PrescriptionController::class)
            ->setArguments([
                new Reference(\App\Module\Prescription\Repository\PrescriptionRepository::class),
                new Reference(\App\Bundles\PatientBundle\Repository\PatientRepository::class),
                new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
                new Reference(\App\Bundles\UserBundle\Repository\UserRepository::class),
                new Reference(\App\Module\Inventory\Repository\InventoryItemRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('prescription.view.any', 'Перегляд будь-якого рецепту');
        $registry->add('prescription.view.own', 'Перегляд власних рецептів');
        $registry->add('prescription.edit.own', 'Редагування власних рецептів');
        $registry->add('prescription.edit.any', 'Редагування будь-яких рецептів');
        $registry->add('prescription.create.own', 'Створення власних рецептів');
        $registry->add('prescription.create.any', 'Створення рецептів від імені будь-якого лікаря');

        $registry->addRoleMapping('admin', ['prescription.view.any', 'prescription.edit.any', 'prescription.create.any']);
        $registry->addRoleMapping('medical_manager', ['prescription.view.any', 'prescription.edit.any', 'prescription.create.any']);
        $registry->addRoleMapping('doctor', ['prescription.view.own', 'prescription.edit.own', 'prescription.create.own']);
        $registry->addRoleMapping('nurse', ['prescription.view.own']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('prescription', PrescriptionPolicy::class);
    }
}
