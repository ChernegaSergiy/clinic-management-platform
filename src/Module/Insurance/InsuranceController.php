<?php

declare(strict_types=1);

namespace App\Module\Insurance;

use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Core\Validation\Validator;
use App\Database\Database;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\Insurance\Service\InsuranceService;

class InsuranceController
{
    private InsuranceService $insuranceService;

    public function __construct(InsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
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

    public function show(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read'); // Reusing billing read permission

        $id = (int)($_GET['id'] ?? 0);
        $company = $this->insuranceService->getInsuranceCompany($id);

        if (!$company) {
            http_response_code(404);
            View::render('errors/error.html.twig', ['message' => '404 Not Found: Insurance company not found.']);
            return;
        }

        View::render('@modules/Insurance/templates/companies/show.html.twig', [
            'company' => $company,
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

        $validator = new Validator(Database::getInstance());
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

    public function edit(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $company = $this->insuranceService->getInsuranceCompany($id);

        if (!$company) {
            http_response_code(404);
            echo "Компанію не знайдено";
            return;
        }

        View::render('@modules/Insurance/templates/companies/edit.html.twig', [
            'company' => $company,
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
    }

    public function update(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $company = $this->insuranceService->getInsuranceCompany($id);

        if (!$company) {
            http_response_code(404);
            echo "Компанію не знайдено";
            return;
        }

        $validator = new Validator(Database::getInstance());
        $rules = [
            'name' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $_SESSION['errors'] = $validator->getErrors();
            // Redirect back to edit form
            header('Location: /insurance/companies/edit?id=' . $id);
            exit();
        }

        $this->insuranceService->updateInsuranceCompany(
            $id,
            $_POST['name'],
            $_POST['contact_person'] ?? null,
            $_POST['phone'] ?? null,
            $_POST['email'] ?? null,
            $_POST['notes'] ?? null
        );

        header('Location: /insurance/companies');
        exit();
    }

    public function delete(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $id = (int)($_POST['id'] ?? 0);
        $this->insuranceService->deleteInsuranceCompany($id);

        header('Location: /insurance/companies');
        exit();
    }

    public function listClaims(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read'); // Reuse billing permission

        $claims = $this->insuranceService->getAllClaims();

        View::render('@modules/Insurance/templates/claims/index.html.twig', [
            'claims' => $claims,
        ]);
    }

    public function showClaim(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read');

        $id = (int)($_GET['id'] ?? 0);
        $claim = $this->insuranceService->getClaimWithDetails($id);

        if (!$claim) {
            http_response_code(404);
            echo "Кейс не знайдено";
            return;
        }

        View::render('@modules/Insurance/templates/claims/show.html.twig', [
            'claim' => $claim,
        ]);
    }

    public function updateClaimStatus(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        $totalPaid = !empty($_POST['total_paid']) ? (float)$_POST['total_paid'] : null;
        $submittedAt = !empty($_POST['submitted_at']) ? $_POST['submitted_at'] : null;

        $this->insuranceService->updateClaimStatus($id, $status, $submittedAt, $totalPaid);

        header('Location: /insurance/claims/show?id=' . $id);
        exit();
    }
}
