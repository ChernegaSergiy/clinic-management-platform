<?php

namespace App\Module\News;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class NewsModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/news', [NewsController::class, 'index']);
        $router->add('GET', '/news/{id}', [NewsController::class, 'show']);
        $router->add('GET', '/admin/news', [NewsController::class, 'adminIndex']);
        $router->add('GET', '/admin/news/new', [NewsController::class, 'create']);
        $router->add('POST', '/admin/news/new', [NewsController::class, 'store']);
        $router->add('GET', '/admin/news/edit/{id}', [NewsController::class, 'edit']);
        $router->add('POST', '/admin/news/edit/{id}', [NewsController::class, 'update']);
        $router->add('POST', '/admin/news/delete/{id}', [NewsController::class, 'delete']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\News\Repository\NewsRepository::class)->setPublic(true);
        $container->register(\App\Module\News\NewsController::class)
            ->setArguments([
                new Reference(\App\Module\News\Repository\NewsRepository::class),
                new Reference(\App\Bundles\UserBundle\Repository\UserRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('news.read', 'Перегляд новин');
        $registry->add('news.manage', 'Керування новинами');

        $registry->addRoleMapping('admin', ['news.read', 'news.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('news', NewsPolicy::class);
    }
}
