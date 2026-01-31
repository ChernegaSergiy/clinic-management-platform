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

    // Example: register repositories (kept simple for now)
    $container->register(\App\Module\Patient\Repository\PatientRepository::class)
        ->setPublic(true);
};
