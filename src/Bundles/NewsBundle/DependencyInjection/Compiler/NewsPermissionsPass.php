<?php

namespace App\Bundles\NewsBundle\DependencyInjection\Compiler;

use App\Bundles\NewsBundle\NewsPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NewsPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['news.read', 'Перегляд новин']);
            $registry->addMethodCall('add', ['news.manage', 'Керування новинами']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['news.read', 'news.manage']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['news', NewsPolicy::class]);
        }
    }
}
