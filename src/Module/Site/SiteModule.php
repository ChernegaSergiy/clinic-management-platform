<?php

namespace App\Module\Site;

use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SiteModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/', [SiteController::class, 'home']);
        $router->add('GET', '/about', [SiteController::class, 'about']);
        $router->add('GET', '/our-team', [SiteController::class, 'ourTeam']);
        $router->add('GET', '/contact', [SiteController::class, 'contact']);
        $router->add('GET', '/sitemap', [SiteController::class, 'sitemap']);
        $router->add('GET', '/privacy', [SiteController::class, 'privacy']);
        $router->add('GET', '/departments', [SiteController::class, 'departments']);
        $router->add('GET', '/doctors', [SiteController::class, 'doctors']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(SiteController::class)->setPublic(true);
    }
}
