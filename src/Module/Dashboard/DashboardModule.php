<?php

namespace App\Module\Dashboard;

use App\Core\BaseModule;
use App\Core\Router;
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
}