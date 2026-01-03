<?php

namespace App\Module\Inventory;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\Inventory\InventoryController;

class InventoryModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/inventory', [InventoryController::class, 'index']);
        $router->add('GET', '/inventory/new', [InventoryController::class, 'create']);
        $router->add('POST', '/inventory/new', [InventoryController::class, 'store']);
        $router->add('GET', '/inventory/show', [InventoryController::class, 'show']);
        $router->add('GET', '/inventory/edit', [InventoryController::class, 'edit']);
        $router->add('POST', '/inventory/edit', [InventoryController::class, 'update']);
    }
}