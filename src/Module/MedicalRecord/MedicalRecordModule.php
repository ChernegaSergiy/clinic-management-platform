<?php

namespace App\Module\MedicalRecord;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\MedicalRecord\MedicalRecordController;

class MedicalRecordModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/medical-records', [MedicalRecordController::class, 'index']);
        $router->add('GET', '/medical-records/new', [MedicalRecordController::class, 'create']);
        $router->add('POST', '/medical-records/new', [MedicalRecordController::class, 'store']);
        $router->add('GET', '/medical-records/show', [MedicalRecordController::class, 'show']);
        $router->add('GET', '/medical-records/edit', [MedicalRecordController::class, 'edit']);
        $router->add('POST', '/medical-records/edit', [MedicalRecordController::class, 'update']);
        $router->add('POST', '/medical-records/attachments/upload', [MedicalRecordController::class, 'uploadAttachment']);
        $router->add('GET', '/medical-records/attachments/download', [MedicalRecordController::class, 'downloadAttachment']);
        $router->add('GET', '/medical-records/icd-codes', [MedicalRecordController::class, 'getIcdCodes']);
        $router->add('GET', '/medical-records/intervention-codes', [MedicalRecordController::class, 'getInterventionCodes']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('medical.read', 'Перегляд медичних записів');
        $registry->add('medical.write', 'Редагування медичних записів');

        $registry->addRoleMapping('admin', ['medical.read', 'medical.write']);
        $registry->addRoleMapping('medical_manager', ['medical.read', 'medical.write']);
        $registry->addRoleMapping('doctor', ['medical.read', 'medical.write']);
        $registry->addRoleMapping('nurse', ['medical.read', 'medical.write']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }
}