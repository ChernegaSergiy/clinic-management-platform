<?php

namespace App\Module\Dashboard;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\Dashboard\DashboardController;

class DashboardModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/dashboard', [DashboardController::class, 'index']);

        if ($this->getConfig('features.export', true)) {
            $router->add('GET', '/dashboard/export-csv', [DashboardController::class, 'exportCsv']);
            $router->add('GET', '/dashboard/export-pdf', [DashboardController::class, 'exportPdf']);
            $router->add('GET', '/dashboard/export-excel', [DashboardController::class, 'exportExcel']);
        }
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('dashboard.view', 'Перегляд панелі');
        $registry->add('dashboard.export', 'Експорт даних');

        $registry->addRoleMapping('admin', ['dashboard.view', 'dashboard.export']);
        $registry->addRoleMapping('medical_manager', ['dashboard.view', 'dashboard.export']);
        $registry->addRoleMapping('registrar', ['dashboard.view']);
        $registry->addRoleMapping('doctor', ['dashboard.view']);
        $registry->addRoleMapping('nurse', ['dashboard.view']);
        $registry->addRoleMapping('lab_technician', ['dashboard.view']);
        $registry->addRoleMapping('billing', ['dashboard.view', 'dashboard.export']);
        $registry->addRoleMapping('inventory_manager', ['dashboard.view']);
        $registry->addRoleMapping('hr_manager', ['dashboard.view']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('dashboard', DashboardPolicy::class);
    }
}
