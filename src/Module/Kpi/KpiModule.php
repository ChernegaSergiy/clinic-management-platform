<?php

namespace App\Module\Kpi;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\Admin\KpiController;

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
}