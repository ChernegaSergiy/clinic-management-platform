<?php

namespace App\Controller;

use App\Core\View;
use App\Core\Validator;
use App\Repository\KpiRepository;

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
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        View::render('kpi/definitions/index.html.twig', ['definitions' => $definitions]);
    }

    public function createDefinition(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        View::render('kpi/definitions/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeDefinition(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required'],
            'kpi_type' => ['required', 'in:appointments_count,revenue_generated,patient_satisfaction'],
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
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        // For now, just show current user's results, or all results for admins
        $userId = $_SESSION['user']['id'];
        $results = $this->kpiRepository->findKpiResultsForUser($userId);
        View::render('kpi/results/index.html.twig', ['results' => $results]);
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
