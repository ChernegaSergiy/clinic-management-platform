<?php

namespace App\Module\Install;

use App\Core\Module\BaseModule;
use App\Core\Http\Router;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InstallModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/install', [InstallController::class, 'check']);
        $router->add('GET', '/api/status', [InstallController::class, 'apiStatus']);
    }

    public function registerServices(ContainerBuilder $container): void
    {
        $container->register(InstallController::class)->setPublic(true);
    }
}
