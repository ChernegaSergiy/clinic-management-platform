<?php

namespace App\Module\Admin;

use App\Core\View;
use App\Core\Validator;
use App\Module\Admin\Repository\KpiRepository;
use App\Core\AuthGuard;

class KpiController
{
    private KpiRepository $kpiRepository;

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
    }

    // --- KPI Definitions ---
    public function listDefinitions(): void
    {
        AuthGuard::check();
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        View::render('@modules/Admin/templates/kpi_definitions/index.html.twig', ['definitions' => $definitions]);
    }

    public function createDefinition(): void
    {
        AuthGuard::check();
        View::render('@modules/Admin/templates/kpi_definitions/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeDefinition(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required'],
            'kpi_type' => ['required', 'in:appointments_count,revenue_generated,patient_satisfaction'],
            'target_value' => ['numeric', 'min_value:0'],
            'unit' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /kpi/definitions/new');
            exit();
        }

        $this->kpiRepository->saveKpiDefinition($_POST);
        $_SESSION['success_message'] = "Визначення KPI успішно додано.";
        header('Location: /kpi/definitions');
        exit();
    }

    // --- KPI Results ---
    public function listResults(): void
    {
        AuthGuard::check();
        $results = [];
        if (isset($_SESSION['user']) && $_SESSION['user']['role_id'] === 1) { // Перевірка, чи користувач є адміністратором
            $results = $this->kpiRepository->findAllKpiResults();
        } else {
            $userId = $_SESSION['user']['id'];
            $results = $this->kpiRepository->findKpiResultsForUser($userId);
        }
        View::render('@modules/Admin/templates/kpi/results/index.html.twig', ['results' => $results]);
    }

    // This would be called by a cron job or background process
    public function calculateResults(): void
    {
        // TODO: Implement actual KPI calculation logic here
        // This would involve fetching data from various repositories (appointments, invoices, etc.)
        // and saving results using $this->kpiRepository->saveKpiResult();
        echo "KPI results calculated (placeholder).";
        exit();
    }
}
