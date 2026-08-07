<?php

namespace App\Bundles\DashboardBundle\Controller;

use App\Bundles\DashboardBundle\Service\DashboardService;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends \App\Core\Controller\AbstractController
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth(); // Ensure user is authenticated
        $this->gate->authorize('dashboard.view');

        $canSeeFinance = $this->gate->allows('billing.read');
        $canSeeKpi = $this->gate->allows('kpi.read');
        $canExport = $this->gate->allows('dashboard.export');

        $dashboardData = $this->dashboardService->getDashboardData();

        return $this->render('dashboard/index.html.twig', [
            'dashboardData' => $dashboardData,
            'canSeeFinance' => $canSeeFinance,
            'canSeeKpi' => $canSeeKpi,
            'canExport' => $canExport,
        ]);
    }

    #[Route('/dashboard/export-csv', name: 'dashboard_export_csv', methods: ['GET'])]
    public function exportCsv() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('dashboard.export');
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
        return new \Symfony\Component\HttpFoundation\Response('', 200); // download will exit, but just in case
    }

    #[Route('/dashboard/export-pdf', name: 'dashboard_export_pdf', methods: ['GET'])]
    public function exportPdf() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('dashboard.export');
        $dashboardData = $this->dashboardService->getDashboardData()['kpis'];

        // Render the Twig template into an HTML string
        $html = $this->view->renderToString('dashboard/pdf_report.html.twig', [
            'dashboardData' => $dashboardData,
        ]);

        $exporter = new \App\Core\Export\PdfExporter();
        $exporter->loadHtml($html);
        $exporter->render();
        $exporter->download('dashboard_report.pdf');
        return new \Symfony\Component\HttpFoundation\Response('', 200);
    }

    #[Route('/dashboard/export-excel', name: 'dashboard_export_excel', methods: ['GET'])]
    public function exportExcel() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('dashboard.export');
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
        return new \Symfony\Component\HttpFoundation\Response('', 200);
    }
}
