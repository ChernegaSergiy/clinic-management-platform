<?php

namespace App\Module\Kpi;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class KpiModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/kpi/definitions', [KpiController::class, 'listDefinitions']);
        $router->add('GET', '/kpi/definitions/new', [KpiController::class, 'createDefinition']);
        $router->add('POST', '/kpi/definitions/new', [KpiController::class, 'storeDefinition']);
        $router->add('GET', '/kpi/definitions/edit', [KpiController::class, 'editDefinition']);
        $router->add('POST', '/kpi/definitions/edit', [KpiController::class, 'updateDefinition']);
        $router->add('POST', '/kpi/definitions/delete', [KpiController::class, 'deleteDefinition']);
        $router->add('GET', '/kpi/results', [KpiController::class, 'listResults']);
        $router->add('POST', '/kpi/calculate', [KpiController::class, 'calculateResults']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Kpi\Repository\KpiRepository::class)->setPublic(true);
        $container->register(\App\Module\Kpi\KpiController::class)
            ->setArguments([
                new Reference(\App\Module\Kpi\Repository\KpiRepository::class),
                new Reference(\App\Bundles\BillingBundle\Repository\InvoiceRepository::class),
                new Reference(\App\Bundles\AppointmentBundle\Repository\AppointmentRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('kpi.read', 'Перегляд KPI');
        $registry->add('kpi.manage', 'Керування KPI');

        $registry->addRoleMapping('admin', ['kpi.read', 'kpi.manage']);
        $registry->addRoleMapping('medical_manager', ['kpi.read']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('kpi', KpiPolicy::class);
    }
}
