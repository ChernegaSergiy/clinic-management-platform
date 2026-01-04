<?php

namespace App\Module\Billing;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\Billing\BillingController;
use App\Module\Billing\ContractController;

class BillingModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/billing', [BillingController::class, 'index']);
        $router->add('GET', '/billing/new', [BillingController::class, 'create']);
        $router->add('POST', '/billing/new', [BillingController::class, 'store']);
        $router->add('GET', '/billing/show', [BillingController::class, 'show']);
        $router->add('GET', '/billing/edit', [BillingController::class, 'edit']);
        $router->add('POST', '/billing/edit', [BillingController::class, 'update']);
        $router->add('POST', '/billing/add-payment', [BillingController::class, 'addPayment']);
        $router->add('GET', '/billing/export-pdf', [BillingController::class, 'exportInvoicesToPdf']);
        $router->add('GET', '/billing/export-excel', [BillingController::class, 'exportInvoicesToExcel']);
        $router->add('GET', '/billing/export-csv', [BillingController::class, 'exportInvoicesToCsv']);

        $router->add('GET', '/billing/contracts', [ContractController::class, 'index']);
        $router->add('GET', '/billing/contracts/new', [ContractController::class, 'create']);
        $router->add('POST', '/billing/contracts/new', [ContractController::class, 'store']);
        $router->add('GET', '/billing/contracts/show', [ContractController::class, 'show']);
        $router->add('GET', '/billing/contracts/edit', [ContractController::class, 'edit']);
        $router->add('POST', '/billing/contracts/edit', [ContractController::class, 'update']);
        $router->add('POST', '/billing/contracts/delete', [ContractController::class, 'delete']);
        $router->add('GET', '/billing/contracts/{id}/download', [ContractController::class, 'downloadFile']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('billing.read', 'Перегляд рахунків');
        $registry->add('billing.manage', 'Керування рахунками');

        $registry->addRoleMapping('admin', ['billing.read', 'billing.manage']);
        $registry->addRoleMapping('billing', ['billing.read', 'billing.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }
}