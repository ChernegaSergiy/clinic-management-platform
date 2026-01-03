<?php

namespace App\Module\News;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\News\NewsController;

class NewsModule extends BaseModule
{
    public function registerRoutes(Router $router): void
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
}