<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

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
        $csvContent = $exporter->generate();

        $response = new \Symfony\Component\HttpFoundation\Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="dashboard_report.csv"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
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
        $pdfContent = $exporter->output();

        $response = new \Symfony\Component\HttpFoundation\Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="dashboard_report.pdf"');

        return $response;
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
        $excelContent = $exporter->generate($headers, $data);

        $response = new \Symfony\Component\HttpFoundation\Response($excelContent);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="dashboard_report.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
