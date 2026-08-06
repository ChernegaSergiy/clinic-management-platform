<?php

namespace App\Module\Billing;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BillingModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
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

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Billing\Repository\InvoiceRepository::class)->setPublic(true);
        $container->register(\App\Module\Billing\Repository\ServiceRepository::class)->setPublic(true);
        $container->register(\App\Module\Billing\Repository\ServiceBundleRepository::class)->setPublic(true);
        $container->register(\App\Module\Billing\Repository\ContractRepository::class)->setPublic(true);

        $container->register(\App\Module\Billing\BillingController::class)
            ->setArguments([
                new Reference(\App\Module\Billing\Repository\InvoiceRepository::class),
                new Reference(\App\Module\Patient\Repository\PatientRepository::class),
                new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
                new Reference(\App\Module\MedicalRecord\Repository\MedicalRecordRepository::class),
                new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
                new Reference(\App\Module\Billing\Repository\ServiceBundleRepository::class),
                new Reference(\App\Module\Insurance\Service\InsuranceService::class),
            ])->setPublic(true);

        $container->register(\App\Module\Billing\ContractController::class)
            ->setArguments([
                new Reference(\App\Module\Billing\Repository\ContractRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('billing.read', 'Перегляд рахунків');
        $registry->add('billing.manage', 'Керування рахунками');

        $registry->addRoleMapping('admin', ['billing.read', 'billing.manage']);
        $registry->addRoleMapping('billing', ['billing.read', 'billing.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('billing', BillingPolicy::class);
    }
}
