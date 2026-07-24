<?php

namespace App\Module\MedicalRecord;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\MedicalRecord\MedicalRecordController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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

    public function registerServices(ContainerBuilder $container): void
    {
        $container->register(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class)->setPublic(true);
        $container->register(\App\Module\MedicalRecord\MedicalRecordController::class)
            ->setArguments([
                new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
                new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
                new Reference(\App\Module\LabOrder\Repository\LabOrderRepository::class),
                new Reference(\App\Module\ClinicalReference\Repository\IcdCodeRepository::class),
                new Reference(\App\Module\ClinicalReference\Repository\InterventionCodeRepository::class),
                new Reference(\App\Core\Service\AttachmentService::class),
                new Reference(\App\Core\Service\AuditLogger::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('medical_record.view.any', 'Перегляд будь-якого медичного запису');
        $registry->add('medical_record.view.own', 'Перегляд власних медичних записів');
        $registry->add('medical_record.edit.own', 'Редагування власних медичних записів');
        $registry->add('medical_record.edit.any', 'Редагування будь-яких медичних записів');
        $registry->add('medical_record.create', 'Створення медичних записів');

        $registry->addRoleMapping('admin', ['medical_record.view.any', 'medical_record.edit.any', 'medical_record.create']);
        $registry->addRoleMapping('medical_manager', ['medical_record.view.any', 'medical_record.edit.any']);
        $registry->addRoleMapping('doctor', ['medical_record.view.own', 'medical_record.edit.own', 'medical_record.create']);
        $registry->addRoleMapping('nurse', ['medical_record.view.own']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('medical_record', MedicalRecordPolicy::class);
    }
}
