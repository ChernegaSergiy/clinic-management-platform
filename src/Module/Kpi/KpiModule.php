<?php

namespace App\Module\Kpi;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\Kpi\Controller\KpiController;

class KpiModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/kpi/definitions', [KpiController::class, 'listDefinitions']);
        $router->add('GET', '/kpi/definitions/new', [KpiController::class, 'createDefinition']);
        $router->add('POST', '/kpi/definitions/new', [KpiController::class, 'storeDefinition']);
        $router->add('GET', '/kpi/results', [KpiController::class, 'listResults']);
        $router->add('POST', '/kpi/calculate', [KpiController::class, 'calculateResults']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('kpi.read', 'Перегляд KPI');

        $registry->addRoleMapping('admin', ['kpi.read']);
        $registry->addRoleMapping('medical_manager', ['kpi.read']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('kpi', KpiPolicy::class);
    }
}
