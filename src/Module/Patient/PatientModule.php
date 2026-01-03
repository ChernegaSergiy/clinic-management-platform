<?php

namespace App\Module\Patient;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\Patient\PatientController;

class PatientModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/patients', [PatientController::class, 'index']);
        $router->add('GET', '/patients/new', [PatientController::class, 'create']);
        $router->add('POST', '/patients/new', [PatientController::class, 'store']);
        $router->add('GET', '/patients/show', [PatientController::class, 'show']);
        $router->add('GET', '/patients/edit', [PatientController::class, 'edit']);
        $router->add('POST', '/patients/edit', [PatientController::class, 'update']);
        $router->add('POST', '/patients/toggle-status', [PatientController::class, 'toggleStatus']);
        $router->add('GET', '/patients/export-csv', [PatientController::class, 'exportCsv']);
        $router->add('GET', '/patients/export-json', [PatientController::class, 'exportPatientsToJson']);
        $router->add('GET', '/patients/import-json', [PatientController::class, 'importPatientsFromJson']);
        $router->add('POST', '/patients/import-json', [PatientController::class, 'importPatientsFromJson']);

        if ($this->getConfig('features.policies', true)) {
            $router->add('GET', '/patients/{patientId}/policies/add', [PatientController::class, 'addPolicy']);
            $router->add('POST', '/patients/{patientId}/policies/store', [PatientController::class, 'storePolicy']);
            $router->add('GET', '/patients/{patientId}/policies/edit', [PatientController::class, 'editPolicy']);
            $router->add('POST', '/patients/{patientId}/policies/update', [PatientController::class, 'updatePolicy']);
            $router->add('POST', '/patients/{patientId}/policies/delete', [PatientController::class, 'deletePolicy']);
        }
    }
}