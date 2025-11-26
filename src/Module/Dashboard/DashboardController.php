<?php

namespace App\Module\Dashboard;

use App\Core\AuthGuard;
use App\Core\View;
use App\Module\Dashboard\Service\DashboardService;

class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function index(): void
    {
        AuthGuard::check(); // Ensure user is authenticated

        $dashboardData = $this->dashboardService->getDashboardData();

        View::render('dashboard/index.html.twig', [
            'dashboardData' => $dashboardData,
        ]);
    }

    public function exportCsv(): void
    {
        AuthGuard::check();
        $dashboardData = $this->dashboardService->getDashboardData()['kpis'];

        $headers = ['Показник', 'Значення', 'Тренд', 'Опис'];
        $data = [];

        foreach ($dashboardData as $kpi) {
            $data[] = [
                $kpi['definition']['name'],
                $kpi['latest_value'],
                $kpi['trend'] ?? 'N/A',
                $kpi['definition']['description'] ?? '',
            ];
        }

        $exporter = new \App\Core\CsvExporter($headers, $data);
        $exporter->download('dashboard_report.csv');
    }

    public function exportPdf(): void
    {
        AuthGuard::check();
        $dashboardData = $this->dashboardService->getDashboardData()['kpis'];

        // Render the Twig template into an HTML string
        $html = \App\Core\View::renderToString('dashboard/pdf_report.html.twig', [
            'dashboardData' => $dashboardData,
        ]);

        $exporter = new \App\Core\PdfExporter();
        $exporter->loadHtml($html);
        $exporter->render();
        $exporter->download('dashboard_report.pdf');
    }
}
