<?php

namespace App\Module\Prescription;

use App\Core\BaseModule;
use App\Core\Router;
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
}