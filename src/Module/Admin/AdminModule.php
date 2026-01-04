<?php

namespace App\Module\Admin;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\Admin\AdminController;

class AdminModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/admin/users', [AdminController::class, 'users']);
        $router->add('GET', '/admin/users/new', [AdminController::class, 'createUser']);
        $router->add('POST', '/admin/users/new', [AdminController::class, 'storeUser']);
        $router->add('GET', '/admin/users/show', [AdminController::class, 'showUser']);
        $router->add('GET', '/admin/users/edit', [AdminController::class, 'editUser']);
        $router->add('POST', '/admin/users/edit', [AdminController::class, 'updateUser']);
        $router->add('POST', '/admin/users/delete', [AdminController::class, 'deleteUser']);

        $router->add('GET', '/admin/roles', [AdminController::class, 'listRoles']);
        $router->add('GET', '/admin/roles/new', [AdminController::class, 'createRole']);
        $router->add('POST', '/admin/roles/new', [AdminController::class, 'storeRole']);
        $router->add('GET', '/admin/roles/edit', [AdminController::class, 'editRole']);
        $router->add('POST', '/admin/roles/edit', [AdminController::class, 'updateRole']);
        $router->add('POST', '/admin/roles/delete', [AdminController::class, 'deleteRole']);

        $router->add('GET', '/admin/dictionaries', [AdminController::class, 'listDictionaries']);
        $router->add('GET', '/admin/dictionaries/new', [AdminController::class, 'createDictionary']);
        $router->add('POST', '/admin/dictionaries/new', [AdminController::class, 'storeDictionary']);
        $router->add('GET', '/admin/dictionaries/show', [AdminController::class, 'showDictionary']);
        $router->add('GET', '/admin/dictionaries/edit', [AdminController::class, 'editDictionary']);
        $router->add('POST', '/admin/dictionaries/edit', [AdminController::class, 'updateDictionary']);
        $router->add('POST', '/admin/dictionaries/delete', [AdminController::class, 'deleteDictionary']);
        $router->add('GET', '/admin/dictionaries/values/new', [AdminController::class, 'createDictionaryValue']);
        $router->add('POST', '/admin/dictionaries/values/new', [AdminController::class, 'storeDictionaryValue']);
        $router->add('GET', '/admin/dictionaries/values/edit', [AdminController::class, 'editDictionaryValue']);
        $router->add('POST', '/admin/dictionaries/values/edit', [AdminController::class, 'updateDictionaryValue']);
        $router->add('POST', '/admin/dictionaries/values/delete', [AdminController::class, 'deleteDictionaryValue']);

        $router->add('GET', '/admin/services', [AdminController::class, 'listServices']);
        $router->add('GET', '/admin/services/new', [AdminController::class, 'createService']);
        $router->add('POST', '/admin/services/new', [AdminController::class, 'storeService']);
        $router->add('GET', '/admin/services/edit', [AdminController::class, 'editService']);
        $router->add('POST', '/admin/services/edit', [AdminController::class, 'updateService']);
        $router->add('POST', '/admin/services/delete', [AdminController::class, 'deleteService']);

        $router->add('GET', '/admin/service-categories', [AdminController::class, 'listServiceCategories']);
        $router->add('GET', '/admin/service-categories/new', [AdminController::class, 'createServiceCategory']);
        $router->add('POST', '/admin/service-categories/new', [AdminController::class, 'storeServiceCategory']);
        $router->add('GET', '/admin/service-categories/edit', [AdminController::class, 'editServiceCategory']);
        $router->add('POST', '/admin/service-categories/edit', [AdminController::class, 'updateServiceCategory']);
        $router->add('POST', '/admin/service-categories/delete', [AdminController::class, 'deleteServiceCategory']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('admin.manage', 'Керування адмінпанеллю');

        $registry->addRoleMapping('admin', ['admin.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }
}