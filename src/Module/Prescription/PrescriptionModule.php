<?php

namespace App\Module\Prescription;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\Prescription\PrescriptionController;

class PrescriptionModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/prescriptions', [PrescriptionController::class, 'index']);
        $router->add('GET', '/prescriptions/new', [PrescriptionController::class, 'create']);
        $router->add('POST', '/prescriptions/new', [PrescriptionController::class, 'store']);
        $router->add('GET', '/prescriptions/show', [PrescriptionController::class, 'show']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('prescription.view.any', 'Перегляд будь-якого рецепту');
        $registry->add('prescription.view.own', 'Перегляд власних рецептів');
        $registry->add('prescription.edit.own', 'Редагування власних рецептів');
        $registry->add('prescription.create.own', 'Створення власних рецептів');
        $registry->add('prescription.create.any', 'Створення рецептів від імені будь-якого лікаря');

        $registry->addRoleMapping('admin', ['prescription.view.any', 'prescription.edit.own', 'prescription.create.any']);
        $registry->addRoleMapping('medical_manager', ['prescription.view.any', 'prescription.create.any']);
        $registry->addRoleMapping('doctor', ['prescription.view.own', 'prescription.edit.own', 'prescription.create.own']);
        $registry->addRoleMapping('nurse', ['prescription.view.own']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('prescription', PrescriptionPolicy::class);
    }
}
