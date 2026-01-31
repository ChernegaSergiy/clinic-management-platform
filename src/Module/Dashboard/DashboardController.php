<?php

namespace App\Module\Dashboard;

use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Module\Dashboard\Service\DashboardService;

class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct(?DashboardService $dashboardService = null)
    {
        $this->dashboardService = $dashboardService ?? new DashboardService();
    }

    public function index(): void
    {
        AuthGuard::check(); // Ensure user is authenticated
        Gate::authorize('dashboard.view');

        $canSeeFinance = Gate::allows('billing.read');
        $canSeeKpi = Gate::allows('kpi.read');
        $canExport = Gate::allows('dashboard.export');

        $dashboardData = $this->dashboardService->getDashboardData();

        View::render('dashboard/index.html.twig', [
            'dashboardData' => $dashboardData,
            'canSeeFinance' => $canSeeFinance,
            'canSeeKpi' => $canSeeKpi,
            'canExport' => $canExport,
        ]);
    }

    public function exportCsv(): void
    {
        AuthGuard::check();
        Gate::authorize('dashboard.export');
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

        $exporter = new \App\Core\Export\CsvExporter($headers, $data);
        $exporter->download('dashboard_report.csv');
    }

    public function exportPdf(): void
    {
        AuthGuard::check();
        Gate::authorize('dashboard.export');
        $dashboardData = $this->dashboardService->getDashboardData()['kpis'];

        // Render the Twig template into an HTML string
        $html = \App\Core\Http\View::renderToString('dashboard/pdf_report.html.twig', [
            'dashboardData' => $dashboardData,
        ]);

        $exporter = new \App\Core\Export\PdfExporter();
        $exporter->loadHtml($html);
        $exporter->render();
        $exporter->download('dashboard_report.pdf');
    }

    public function exportExcel(): void
    {
        AuthGuard::check();
        Gate::authorize('dashboard.export');
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

        $exporter = new \App\Core\Export\ExcelExporter();
        $exporter->export($headers, $data, 'dashboard_report.xlsx');
    }
}
