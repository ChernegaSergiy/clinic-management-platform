<?php

namespace App\Module\Inventory;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
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

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('inventory.manage', 'Керування складом');

        $registry->addRoleMapping('admin', ['inventory.manage']);
        $registry->addRoleMapping('inventory_manager', ['inventory.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('inventory', InventoryPolicy::class);
    }
}
