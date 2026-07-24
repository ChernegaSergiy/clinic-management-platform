<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

return function (ContainerBuilder $container) {
    // Doctrine EntityManager
    $container->register(\Doctrine\ORM\EntityManagerInterface::class)
        ->setFactory([\App\Infrastructure\Database\DoctrineFactory::class, 'createEntityManager'])
        ->setPublic(true);

    // PDO service via existing Database singleton.
    $container->register('pdo', PDO::class)
        ->setFactory([\App\Database\Database::class, 'getInstance'])
        ->setPublic(true);

    // Core services
    $container->register(\App\Core\Http\Router::class)
        ->setArguments([new Reference('service_container')])
        ->setPublic(true);

    $container->register(\App\Core\Module\ModuleManager::class)->setPublic(true);
    $container->register(\App\Core\Auth\PermissionRegistry::class)->setPublic(true);
    $container->register(\App\Core\Auth\PolicyRegistry::class)->setPublic(true);
    $container->register(\Symfony\Component\EventDispatcher\EventDispatcher::class)->setPublic(true);
    $container->register(\App\Core\Service\TranslationService::class)->setPublic(true);
    $container->register(\App\Core\Service\AuditLogger::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);

    $container->register(\App\Core\Service\AttachmentService::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);
    $container->register(\App\Core\Service\NotificationService::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);
    $container->register(\App\Core\Service\QrCodeGenerator::class)->setPublic(true);
    
    $container->register(\App\Core\Repository\SettingsRepository::class)->setPublic(true);
};
