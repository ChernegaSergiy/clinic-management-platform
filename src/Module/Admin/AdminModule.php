<?php

namespace App\Module\Admin;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AdminModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
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

        $router->add('GET', '/admin/auth_configs', [AdminController::class, 'listAuthConfigs']);
        $router->add('GET', '/admin/auth_configs/new', [AdminController::class, 'createAuthConfig']);
        $router->add('POST', '/admin/auth_configs/new', [AdminController::class, 'storeAuthConfig']);
        $router->add('GET', '/admin/auth_configs/edit', [AdminController::class, 'editAuthConfig']);
        $router->add('POST', '/admin/auth_configs/edit', [AdminController::class, 'updateAuthConfig']);
        $router->add('POST', '/admin/auth_configs/delete', [AdminController::class, 'deleteAuthConfig']);
        $router->add('GET', '/admin/auth_configs/show', [AdminController::class, 'showAuthConfig']);
        $router->add('POST', '/admin/users/disable-mfa', [AdminController::class, 'disableUserMfa']);

        $router->add('GET', '/admin/settings', [AdminController::class, 'showSettings']);
        $router->add('POST', '/admin/settings', [AdminController::class, 'updateSettings']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Admin\Repository\AuthConfigRepository::class)->setPublic(true);
        $container->register(\App\Module\Admin\Repository\BackupPolicyRepository::class)->setPublic(true);
        $container->register(\App\Module\Admin\Repository\DictionaryRepository::class)->setPublic(true);

        $container->register(\App\Module\Admin\AdminController::class)
            ->setArguments([
                new Reference(\App\Bundles\UserBundle\Repository\UserRepository::class),
                new Reference(\App\Bundles\UserBundle\Repository\RoleRepository::class),
                new Reference(\App\Module\Admin\Repository\DictionaryRepository::class),
                new Reference(\App\Module\Admin\Repository\AuthConfigRepository::class),
                new Reference(\App\Module\Admin\Repository\BackupPolicyRepository::class),
                new Reference(\App\Module\Kpi\Repository\KpiRepository::class),
                new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
                new Reference(\App\Core\Repository\SettingsRepository::class),
                new Reference(\App\Bundles\UserBundle\MfaService::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('admin.manage', 'Керування адмінпанеллю');

        $registry->addRoleMapping('admin', ['admin.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void {}
}
