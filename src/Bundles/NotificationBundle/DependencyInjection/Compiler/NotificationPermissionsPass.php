<?php

namespace App\Bundles\NotificationBundle\DependencyInjection\Compiler;

use App\Core\Auth\PermissionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NotificationPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['notifications.read', 'Перегляд сповіщень']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['registrar', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['doctor', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['nurse', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['lab_technician', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['billing', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['inventory_manager', ['notifications.read']]);
            $registry->addMethodCall('addRoleMapping', ['hr_manager', ['notifications.read']]);
        }
    }
}
