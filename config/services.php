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
};
