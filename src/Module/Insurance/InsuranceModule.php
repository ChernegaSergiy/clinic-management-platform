<?php

namespace App\Module\Insurance;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\Insurance\InsuranceController;

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
