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

    $container->register(\Symfony\Component\EventDispatcher\EventDispatcher::class)->setPublic(true);
    $container->setAlias(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class, \Symfony\Component\EventDispatcher\EventDispatcher::class);
    $container->register(\App\Shared\Service\AuditLogger::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);

    $container->register(\App\Shared\Service\AttachmentService::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);
    $container->register(\App\Shared\Service\NotificationService::class)
        ->setArguments([new Reference('pdo')])
        ->setPublic(true);
    $container->register(\App\Shared\Service\QrCodeGenerator::class)->setPublic(true);

    $container->register(\App\Shared\Repository\SettingsRepository::class)->setPublic(true);
};
