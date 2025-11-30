<?php

declare(strict_types=1);

namespace App\Module\Insurance;

use App\Core\AuthGuard;
use App\Core\Gate;
use App\Core\View;
use App\Module\Insurance\Service\InsuranceService;

class InsuranceController
{
    private InsuranceService $insuranceService;

    public function __construct()
    {
        $database = \App\Database::getInstance();
        $this->insuranceService = new InsuranceService(
            new \App\Module\Insurance\Repository\InsuranceCompanyRepository($database),
            new \App\Module\Insurance\Repository\PatientInsurancePolicyRepository($database),
            new \App\Module\Insurance\Repository\ClaimRepository($database)
        );
    }

    public function index(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage'); // Reuse billing permission for now

        $companies = $this->insuranceService->getAllInsuranceCompanies();

        View::render('@modules/Insurance/templates/companies/index.html.twig', [
            'companies' => $companies,
        ]);
    }
}
