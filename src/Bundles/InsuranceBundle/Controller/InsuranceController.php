<?php

declare(strict_types=1);

namespace App\Bundles\InsuranceBundle\Controller;

use App\Bundles\InsuranceBundle\Service\InsuranceService;
use App\Core\Validation\Validator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InsuranceController extends \App\Core\Controller\AbstractController
{
    private InsuranceService $insuranceService;
    private Validator $validator;

    public function __construct(InsuranceService $insuranceService, Validator $validator)
    {
        $this->insuranceService = $insuranceService;
        $this->validator = $validator;
    }

    #[Route('/insurance/companies', name: 'insurance_companies_index', methods: ['GET'])]
    public function index() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage'); // Reuse billing permission for now

        $companies = $this->insuranceService->getAllInsuranceCompanies();

        return $this->render('@Insurance/companies/index.html.twig', [
            'companies' => $companies,
        ]);
    }

    #[Route('/insurance/companies/show', name: 'insurance_companies_show', methods: ['GET'])]
    public function show() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read'); // Reusing billing read permission

        $id = (int)($_GET['id'] ?? 0);
        $company = $this->insuranceService->getInsuranceCompany($id);

        if (!$company) {
            return $this->render('errors/error.html.twig', ['message' => '404 Not Found: Insurance company not found.'], new Response('', 404));
        }

        return $this->render('@Insurance/companies/show.html.twig', [
            'company' => $company,
        ]);
    }

    #[Route('/insurance/companies/new', name: 'insurance_companies_new_get', methods: ['GET'])]
    public function create() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $response = $this->render('@Insurance/companies/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/insurance/companies/new', name: 'insurance_companies_new_post', methods: ['POST'])]
    public function store() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $validator = $this->validator;
        $rules = [
            'name' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/insurance/companies/new');
        }

        $this->insuranceService->addInsuranceCompany(
            $_POST['name'],
            $_POST['contact_person'] ?? null,
            $_POST['phone'] ?? null,
            $_POST['email'] ?? null,
            $_POST['notes'] ?? null
        );

        return new RedirectResponse('/insurance/companies');
    }

    #[Route('/insurance/companies/edit', name: 'insurance_companies_edit_get', methods: ['GET'])]
    public function edit() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $company = $this->insuranceService->getInsuranceCompany($id);

        if (!$company) {
            return new Response("Компанію не знайдено", 404);
        }

        $response = $this->render('@Insurance/companies/edit.html.twig', [
            'company' => $company,
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
        return $response;
    }

    #[Route('/insurance/companies/edit', name: 'insurance_companies_edit_post', methods: ['POST'])]
    public function update() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $company = $this->insuranceService->getInsuranceCompany($id);

        if (!$company) {
            return new Response("Компанію не знайдено", 404);
        }

        $validator = $this->validator;
        $rules = [
            'name' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $_SESSION['errors'] = $validator->getErrors();
            // Redirect back to edit form
            return new RedirectResponse('/insurance/companies/edit?id=' . $id);
        }

        $this->insuranceService->updateInsuranceCompany(
            $id,
            $_POST['name'],
            $_POST['contact_person'] ?? null,
            $_POST['phone'] ?? null,
            $_POST['email'] ?? null,
            $_POST['notes'] ?? null
        );

        return new RedirectResponse('/insurance/companies');
    }

    #[Route('/insurance/companies/delete', name: 'insurance_companies_delete', methods: ['POST'])]
    public function delete() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $id = (int)($_POST['id'] ?? 0);
        $this->insuranceService->deleteInsuranceCompany($id);

        return new RedirectResponse('/insurance/companies');
    }

    #[Route('/insurance/claims', name: 'insurance_claims_index', methods: ['GET'])]
    public function listClaims() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read'); // Reuse billing permission

        $claims = $this->insuranceService->getAllClaims();

        return $this->render('@Insurance/claims/index.html.twig', [
            'claims' => $claims,
        ]);
    }

    #[Route('/insurance/claims/show', name: 'insurance_claims_show', methods: ['GET'])]
    public function showClaim() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read');

        $id = (int)($_GET['id'] ?? 0);
        $claim = $this->insuranceService->getClaimWithDetails($id);

        if (!$claim) {
            return new Response("Кейс не знайдено", 404);
        }

        return $this->render('@Insurance/claims/show.html.twig', [
            'claim' => $claim,
        ]);
    }

    #[Route('/insurance/claims/update-status', name: 'insurance_claims_update_status', methods: ['POST'])]
    public function updateClaimStatus() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        $totalPaid = !empty($_POST['total_paid']) ? (float)$_POST['total_paid'] : null;
        $submittedAt = !empty($_POST['submitted_at']) ? $_POST['submitted_at'] : null;

        $this->insuranceService->updateClaimStatus($id, $status, $submittedAt, $totalPaid);

        return new RedirectResponse('/insurance/claims/show?id=' . $id);
    }
}
