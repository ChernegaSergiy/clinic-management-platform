<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

return function (ContainerBuilder $container) {
    // PDO service via existing Database singleton.
    $container->register('pdo', PDO::class)
        ->setFactory([\App\Database\Database::class, 'getInstance'])
        ->setPublic(true);

    // Core services
    // Event dispatcher
    $container->register(\Symfony\Component\EventDispatcher\EventDispatcher::class)
        ->setPublic(true);

    $container->register(\App\Core\Service\TranslationService::class)
        ->setPublic(true);

    $container->register(\App\Core\Service\AuditLogger::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);

    // Repositories
    $container->register(\App\Module\Patient\Repository\PatientRepository::class)
        ->setArguments([new Reference('pdo'), new Reference(\App\Core\Service\AuditLogger::class)])
        ->setPublic(true);

    // Controllers (allow resolving controllers from container)
    $container->register(\App\Controller\PageController::class)
        ->setPublic(true);
    $container->register(\App\Controller\InstallController::class)
        ->setPublic(true);

    // MedicalRecord module services
    $container->register(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class)
        ->setPublic(true);
    $container->register(\App\Module\Appointment\Repository\AppointmentRepository::class)
        ->setPublic(true);
    $container->register(\App\Module\LabOrder\Repository\LabOrderRepository::class)
        ->setPublic(true);
    $container->register(\App\Module\ClinicalReference\Repository\IcdCodeRepository::class)
        ->setPublic(true);
    $container->register(\App\Module\ClinicalReference\Repository\InterventionCodeRepository::class)
        ->setPublic(true);

    // Core utility services
    $container->register(\App\Core\Service\AttachmentService::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);
    $container->register(\App\Core\Service\NotificationService::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);
    $container->register(\App\Core\Service\QrCodeGenerator::class)
        ->setPublic(true);
    $container->register(\App\Module\MedicalRecord\MedicalRecordController::class)
        ->setArguments([
            new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
            new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
            new Reference(\App\Module\LabOrder\Repository\LabOrderRepository::class),
            new Reference(\App\Module\ClinicalReference\Repository\IcdCodeRepository::class),
            new Reference(\App\Module\ClinicalReference\Repository\InterventionCodeRepository::class),
            new Reference(\App\Core\Service\AttachmentService::class),
            new Reference(\App\Core\Service\AuditLogger::class),
        ])
        ->setPublic(true);

    // Patient module
    $container->register(\App\Module\Patient\Repository\PatientRepository::class)
        ->setArguments([new Reference('pdo'), new Reference(\App\Core\Service\AuditLogger::class)])
        ->setPublic(true);
    $container->register(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class)->setPublic(true);
    $container->register(\App\Module\Appointment\Repository\AppointmentRepository::class)->setPublic(true);
    $container->register(\App\Module\Insurance\Repository\InsuranceCompanyRepository::class)->setPublic(true);
    $container->register(\App\Module\Insurance\Repository\PatientInsurancePolicyRepository::class)->setPublic(true);
    $container->register(\App\Module\Insurance\Repository\ClaimRepository::class)->setPublic(true);
    $container->register(\App\Module\Billing\Repository\InvoiceRepository::class)->setPublic(true);
    $container->register(\App\Module\Insurance\Service\InsuranceService::class)
        ->setArguments([
            new Reference(\App\Module\Insurance\Repository\InsuranceCompanyRepository::class),
            new Reference(\App\Module\Insurance\Repository\PatientInsurancePolicyRepository::class),
            new Reference(\App\Module\Insurance\Repository\ClaimRepository::class),
            new Reference(\App\Module\Billing\Repository\InvoiceRepository::class),
        ])
        ->setPublic(true);
    $container->register(\App\Module\Patient\PatientController::class)
        ->setArguments([
            new Reference(\App\Module\Patient\Repository\PatientRepository::class),
            new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
            new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
            new Reference(\App\Module\Insurance\Service\InsuranceService::class),
            new Reference(\App\Module\Insurance\Repository\InsuranceCompanyRepository::class),
            new Reference(\App\Module\Insurance\Repository\PatientInsurancePolicyRepository::class),
        ])
        ->setPublic(true);

    // LabOrder module
    $container->register(\App\Module\LabOrder\Repository\LabOrderRepository::class)->setPublic(true);
    $container->register(\App\Module\LabOrder\Repository\LabResourceRepository::class)->setPublic(true);
    $container->register(\App\Module\Inventory\Repository\InventoryItemRepository::class)->setPublic(true);
    $container->register(\App\Module\User\Repository\UserRepository::class)->setPublic(true);
    $container->register(\App\Module\User\Repository\RoleRepository::class)->setPublic(true);
    $container->register(\App\Module\User\Repository\UserOAuthIdentityRepository::class)->setPublic(true);
    $container->register(\App\Module\LabOrder\Service\LabImportService::class)
        ->setPublic(true);
    $container->register(\App\Module\LabOrder\LabOrderController::class)
        ->setArguments([
            new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
            new Reference(\App\Module\LabOrder\Repository\LabOrderRepository::class),
            new Reference(\App\Module\User\Repository\UserRepository::class),
            new Reference(\App\Core\Service\NotificationService::class),
            new Reference(\App\Core\Service\QrCodeGenerator::class),
            new Reference(\App\Module\LabOrder\Service\LabImportService::class),
        ])
        ->setPublic(true);

    // Appointment module
    $container->register(\App\Module\Appointment\Repository\AppointmentRepository::class)->setPublic(true);
    $container->register(\App\Module\Patient\Repository\PatientRepository::class)->setPublic(true);
    $container->register(\App\Module\User\Repository\UserRepository::class)->setPublic(true);
    $container->register(\App\Module\Billing\Repository\ServiceRepository::class)->setPublic(true);
    $container->register(\App\Module\Billing\Repository\ServiceBundleRepository::class)->setPublic(true);
    $container->register(\App\Module\Schedule\Repository\DoctorScheduleRepository::class)->setPublic(true);
    $container->register(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class)->setPublic(true);
    $container->register(\App\Module\Room\Repository\RoomRepository::class)->setPublic(true);
    $container->register(\App\Module\Schedule\Service\SchedulingService::class)
        ->setArguments([
            new Reference(\App\Module\Schedule\Repository\DoctorScheduleRepository::class),
            new Reference(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class),
            new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
            new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
            new Reference(\App\Module\Room\Repository\RoomRepository::class),
        ])
        ->setPublic(true);
    $container->register(\App\Module\Appointment\AppointmentController::class)
        ->setArguments([
            new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
            new Reference(\App\Module\Patient\Repository\PatientRepository::class),
            new Reference(\App\Module\User\Repository\UserRepository::class),
            new Reference(\App\Core\Service\NotificationService::class),
            new Reference(\App\Module\Schedule\Service\SchedulingService::class),
            new Reference(\App\Module\Schedule\Repository\DoctorScheduleRepository::class),
            new Reference(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class),
            new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
            new Reference(\App\Module\Room\Repository\RoomRepository::class),
        ])
        ->setPublic(true);

    // Inventory
    $container->register(\App\Module\Inventory\Repository\InventoryItemRepository::class)->setPublic(true);
    $container->register(\App\Module\Inventory\InventoryController::class)
        ->setArguments([
            new Reference(\App\Module\Inventory\Repository\InventoryItemRepository::class),
        ])->setPublic(true);

    // News
    $container->register(\App\Module\News\Repository\NewsRepository::class)->setPublic(true);
    $container->register(\App\Module\News\NewsController::class)
        ->setArguments([
            new Reference(\App\Module\News\Repository\NewsRepository::class),
            new Reference(\App\Module\User\Repository\UserRepository::class),
        ])->setPublic(true);

    // Schedule
    $container->register(\App\Module\Schedule\Repository\DoctorScheduleRepository::class)->setPublic(true);
    $container->register(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class)->setPublic(true);
    $container->register(\App\Module\Schedule\ScheduleController::class)
        ->setArguments([
            new Reference(\App\Module\Schedule\Repository\DoctorScheduleRepository::class),
            new Reference(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class),
            new Reference(\App\Module\User\Repository\UserRepository::class),
        ])->setPublic(true);

    // Department
    $container->register(\App\Module\Department\Repository\DepartmentRepository::class)->setPublic(true);
    $container->register(\App\Module\Department\Repository\HrmRepository::class)->setPublic(true);
    $container->register(\App\Module\Department\DepartmentController::class)
        ->setArguments([
            new Reference(\App\Module\Department\Repository\DepartmentRepository::class),
            new Reference(\App\Module\Department\Repository\HrmRepository::class),
        ])->setPublic(true);

    // Room
    $container->register(\App\Module\Room\RoomRepository::class)->setPublic(true);
    $container->register(\App\Module\Room\RoomController::class)
        ->setArguments([
            new Reference(\App\Module\Room\Repository\RoomRepository::class),
        ])->setPublic(true);

    // Core repositories
    $container->register(\App\Core\Repository\SettingsRepository::class)->setPublic(true);

    // User services
    $container->register(\App\Module\User\MfaService::class)
        ->setArguments([
            new Reference('pdo'),
            new Reference(\App\Core\Service\QrCodeGenerator::class),
        ])->setPublic(true);

    // Admin
    $container->register(\App\Module\Admin\Repository\AuthConfigRepository::class)->setPublic(true);
    $container->register(\App\Module\Admin\Repository\BackupPolicyRepository::class)->setPublic(true);
    $container->register(\App\Module\Admin\Repository\DictionaryRepository::class)->setPublic(true);
    $container->register(\App\Module\Kpi\Repository\KpiRepository::class)->setPublic(true);
    $container->register(\App\Module\Admin\AdminController::class)
        ->setArguments([
            new Reference(\App\Module\User\Repository\UserRepository::class),
            new Reference(\App\Module\User\Repository\RoleRepository::class),
            new Reference(\App\Module\Admin\Repository\DictionaryRepository::class),
            new Reference(\App\Module\Admin\Repository\AuthConfigRepository::class),
            new Reference(\App\Module\Admin\Repository\BackupPolicyRepository::class),
            new Reference(\App\Module\Kpi\Repository\KpiRepository::class),
            new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
            new Reference(\App\Core\Repository\SettingsRepository::class),
            new Reference(\App\Module\User\MfaService::class),
        ])->setPublic(true);

    // User controllers
    $container->register(\App\Module\User\AuthController::class)
        ->setArguments([
            new Reference(\App\Module\User\Repository\UserRepository::class),
            new Reference(\App\Module\Admin\Repository\AuthConfigRepository::class),
            new Reference(\App\Module\User\Repository\RoleRepository::class),
            new Reference(\App\Module\User\MfaService::class),
        ])->setPublic(true);
    $container->register(\App\Module\User\MfaController::class)
        ->setArguments([
            new Reference(\App\Module\User\MfaService::class),
            new Reference(\App\Module\User\Repository\UserRepository::class),
            new Reference(\App\Core\Repository\SettingsRepository::class),
            new Reference(\App\Module\User\Repository\RoleRepository::class),
        ])->setPublic(true);
    $container->register(\App\Module\User\OAuthController::class)
        ->setArguments([
            new Reference(\App\Module\Admin\Repository\AuthConfigRepository::class),
            new Reference(\App\Module\User\Repository\UserRepository::class),
            new Reference(\App\Module\User\Repository\UserOAuthIdentityRepository::class),
        ])->setPublic(true);

    // Kpi
    $container->register(\App\Module\Kpi\Repository\KpiRepository::class)->setPublic(true);
    $container->register(\App\Module\Kpi\KpiController::class)
        ->setArguments([
            new Reference(\App\Module\Kpi\Repository\KpiRepository::class),
            new Reference(\App\Module\Billing\Repository\InvoiceRepository::class),
            new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
        ])->setPublic(true);

    // Billing controller
    $container->register(\App\Module\Billing\BillingController::class)
        ->setArguments([
            new Reference(\App\Module\Billing\Repository\InvoiceRepository::class),
            new Reference(\App\Module\Patient\Repository\PatientRepository::class),
            new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
            new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
            new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
            new Reference(\App\Module\Billing\Repository\ServiceBundleRepository::class),
            new Reference(\App\Module\Insurance\Service\InsuranceService::class),
        ])->setPublic(true);

    // Prescription
    $container->register(\App\Module\Prescription\Repository\PrescriptionRepository::class)->setPublic(true);
    $container->register(\App\Module\Prescription\PrescriptionController::class)
        ->setArguments([
            new Reference(\App\Module\Prescription\Repository\PrescriptionRepository::class),
            new Reference(\App\Module\Patient\Repository\PatientRepository::class),
            new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
            new Reference(\App\Module\User\Repository\UserRepository::class),
            new Reference(\App\Module\Inventory\Repository\InventoryItemRepository::class),
        ])->setPublic(true);

    // Insurance controller wired to InsuranceService
    $container->register(\App\Module\Insurance\InsuranceController::class)
        ->setArguments([
            new Reference(\App\Module\Insurance\Service\InsuranceService::class),
        ])->setPublic(true);

    // Notification
    $container->register(\App\Module\Notification\Repository\NotificationRepository::class)->setPublic(true);
    $container->register(\App\Module\Notification\NotificationController::class)
        ->setArguments([
            new Reference(\App\Module\Notification\Repository\NotificationRepository::class),
        ])->setPublic(true);

    // Kpi
    $container->register(\App\Module\Kpi\Repository\KpiRepository::class)->setPublic(true);
    $container->register(\App\Module\Kpi\KpiController::class)
        ->setArguments([
            new Reference(\App\Module\Kpi\Repository\KpiRepository::class),
            new Reference(\App\Module\Billing\Repository\InvoiceRepository::class),
            new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
        ])->setPublic(true);

    // Billing - Contract controller
    $container->register(\App\Module\Billing\Repository\ContractRepository::class)->setPublic(true);
    $container->register(\App\Module\Billing\ContractController::class)
        ->setArguments([
            new Reference(\App\Module\Billing\Repository\ContractRepository::class),
        ])->setPublic(true);

    // Dashboard
    $container->register(\App\Module\Dashboard\Service\DashboardService::class)->setPublic(true);
    $container->register(\App\Module\Dashboard\DashboardController::class)
        ->setArguments([
            new Reference(\App\Module\Dashboard\Service\DashboardService::class),
        ])->setPublic(true);

    // ClinicalReference
    $container->register(\App\Module\ClinicalReference\Repository\IcdCodeRepository::class)->setPublic(true);
    $container->register(\App\Module\ClinicalReference\Repository\InterventionCodeRepository::class)->setPublic(true);
    $container->register(\App\Module\ClinicalReference\ClinicalReferenceController::class)
        ->setArguments([
            new Reference(\App\Module\ClinicalReference\Repository\IcdCodeRepository::class),
            new Reference(\App\Module\ClinicalReference\Repository\InterventionCodeRepository::class),
        ])->setPublic(true);
};
