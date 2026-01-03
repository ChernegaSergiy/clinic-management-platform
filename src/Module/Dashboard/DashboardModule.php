<?php

namespace App\Module\Dashboard;

use App\Core\BaseModule;
use App\Core\Router;
use App\Module\Dashboard\DashboardController;
use App\Module\HRM\HrmController;

class DashboardModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/dashboard', [DashboardController::class, 'index']);
        $router->add('GET', '/dashboard/hrm', [HrmController::class, 'index']);
        $router->add('GET', '/dashboard/hrm/new', [HrmController::class, 'create']);
        $router->add('POST', '/dashboard/hrm/new', [HrmController::class, 'store']);
        $router->add('GET', '/dashboard/hrm/show', [HrmController::class, 'show']);
        $router->add('GET', '/dashboard/hrm/edit', [HrmController::class, 'edit']);
        $router->add('POST', '/dashboard/hrm/edit', [HrmController::class, 'update']);
        $router->add('POST', '/dashboard/hrm/toggle-status', [HrmController::class, 'toggleStatus']);

        if ($this->getConfig('features.export', true)) {
            $router->add('GET', '/dashboard/export-csv', [DashboardController::class, 'exportCsv']);
            $router->add('GET', '/dashboard/export-pdf', [DashboardController::class, 'exportPdf']);
            $router->add('GET', '/dashboard/export-excel', [DashboardController::class, 'exportExcel']);
        }
    }
}