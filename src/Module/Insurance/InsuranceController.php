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

    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        View::render('@modules/Insurance/templates/companies/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $validator = new \App\Core\Validator();
        $rules = [
            'name' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /insurance/companies/new');
            exit();
        }

        $this->insuranceService->addInsuranceCompany(
            $_POST['name'],
            $_POST['contact_person'] ?? null,
            $_POST['phone'] ?? null,
            $_POST['email'] ?? null,
            $_POST['notes'] ?? null
        );

        header('Location: /insurance/companies');
        exit();
    }
}
