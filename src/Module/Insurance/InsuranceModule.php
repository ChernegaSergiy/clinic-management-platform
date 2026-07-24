<?php

namespace App\Module\Insurance;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\Insurance\InsuranceController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class InsuranceModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/insurance/companies', [InsuranceController::class, 'index']);
        $router->add('GET', '/insurance/companies/show', [InsuranceController::class, 'show']);
        $router->add('GET', '/insurance/companies/new', [InsuranceController::class, 'create']);
        $router->add('POST', '/insurance/companies/new', [InsuranceController::class, 'store']);
        $router->add('GET', '/insurance/companies/edit', [InsuranceController::class, 'edit']);
        $router->add('POST', '/insurance/companies/edit', [InsuranceController::class, 'update']);
        $router->add('POST', '/insurance/companies/delete', [InsuranceController::class, 'delete']);
        $router->add('GET', '/insurance/claims', [InsuranceController::class, 'listClaims']);
        $router->add('GET', '/insurance/claims/show', [InsuranceController::class, 'showClaim']);
        $router->add('POST', '/insurance/claims/update-status', [InsuranceController::class, 'updateClaimStatus']);
    }

    public function registerServices(ContainerBuilder $container): void
    {
        $container->register(\App\Module\Insurance\Repository\InsuranceCompanyRepository::class)->setPublic(true);
        $container->register(\App\Module\Insurance\Repository\PatientInsurancePolicyRepository::class)->setPublic(true);
        $container->register(\App\Module\Insurance\Repository\ClaimRepository::class)->setPublic(true);
        $container->register(\App\Module\Insurance\Service\InsuranceService::class)
            ->setArguments([
                new Reference(\App\Module\Insurance\Repository\InsuranceCompanyRepository::class),
                new Reference(\App\Module\Insurance\Repository\PatientInsurancePolicyRepository::class),
                new Reference(\App\Module\Insurance\Repository\ClaimRepository::class),
                new Reference(\App\Module\Billing\Repository\InvoiceRepository::class),
            ])->setPublic(true);
        $container->register(\App\Module\Insurance\InsuranceController::class)
            ->setArguments([
                new Reference(\App\Module\Insurance\Service\InsuranceService::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('insurance.manage', 'Керування страховкою');

        $registry->addRoleMapping('admin', ['insurance.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('insurance', InsurancePolicy::class);
    }
}
