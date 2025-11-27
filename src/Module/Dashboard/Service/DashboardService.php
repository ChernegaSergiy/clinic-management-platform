<?php

namespace App\Module\Dashboard\Service;

use App\Module\Admin\Repository\KpiRepository;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\Patient\Repository\PatientRepository;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\LabOrder\Repository\LabOrderRepository;
use App\Module\Inventory\Repository\InventoryItemRepository;
use DateTime;

class DashboardService
{
    private KpiRepository $kpiRepository;
    private InvoiceRepository $invoiceRepository;
    private PatientRepository $patientRepository;
    private AppointmentRepository $appointmentRepository;
    private LabOrderRepository $labOrderRepository;
    private InventoryItemRepository $inventoryItemRepository;

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
        $this->invoiceRepository = new InvoiceRepository();
        $this->patientRepository = new PatientRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->labOrderRepository = new LabOrderRepository();
        $this->inventoryItemRepository = new InventoryItemRepository();
    }

    /**
     * Fetches the latest KPI results and chart data for the dashboard.
     *
     * @return array An associative array containing kpi_results and chart_data.
     */
    public function getDashboardData(): array
    {
        $dashboardKpis = [];
        $kpiDefinitions = $this->kpiRepository->findAllKpiDefinitions();

        foreach ($kpiDefinitions as $definition) {
            $latestResult = $this->kpiRepository->findLatestKpiResult($definition['id']);
            if ($latestResult) {
                // Fetch previous period's result for comparison/trend
                $previousResult = $this->kpiRepository->findKpiResultForPreviousPeriod(
                    $definition['id'],
                    $latestResult['period_start'],
                    'day' // Assuming daily calculation for simplicity
                );

                $dashboardKpis[$definition['kpi_type']] = [
                    'definition' => $definition,
                    'latest_value' => (float)$latestResult['calculated_value'],
                    'period_start' => $latestResult['period_start'],
                    'period_end' => $latestResult['period_end'],
                    'trend' => $this->calculateTrend($latestResult['calculated_value'], $previousResult['calculated_value'] ?? null)
                ];
            }
        }

        // Prepare data for the revenue chart
        $endDate = new DateTime();
        $startDate = (new DateTime())->modify('-6 days');
        $rawChartData = $this->invoiceRepository->getDailyRevenueForPeriod($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));

        // Format data for Chart.js
        $chartData = [
            'labels' => [],
            'data' => [],
        ];
        $revenueByDate = array_column($rawChartData, 'total_revenue', 'date');

        for ($i = 0; $i < 7; $i++) {
            $date = (new DateTime())->modify("-$i days")->format('Y-m-d');
            $chartData['labels'][] = $date;
            $chartData['data'][] = (float)($revenueByDate[$date] ?? 0.0);
        }
        // Reverse to show oldest date first
        $chartData['labels'] = array_reverse($chartData['labels']);
        $chartData['data'] = array_reverse($chartData['data']);

        $startPeriod = (new DateTime())->modify('-6 days')->format('Y-m-d');
        $endPeriod = (new DateTime())->format('Y-m-d');

        $quickStats = [
            'patients_total' => $this->patientRepository->countAll(),
            'appointments_today' => $this->appointmentRepository->countScheduledByDate((new DateTime())->format('Y-m-d')),
            'revenue_7d' => $this->invoiceRepository->sumRevenueForPeriod($startPeriod, $endPeriod),
            'lab_pending' => $this->labOrderRepository->countByStatus(['ordered', 'in_progress']),
            'low_stock' => $this->inventoryItemRepository->countItemsBelowMinStock(),
        ];

        return [
            'kpis' => $dashboardKpis,
            'revenueChart' => $chartData,
            'quickStats' => $quickStats,
        ];
    }

    private function calculateTrend(float $currentValue, ?float $previousValue): ?string
    {
        if ($previousValue === null || $previousValue === 0.0) {
            return null; // No previous data or no change if previous was zero
        }

        $percentageChange = (($currentValue - $previousValue) / $previousValue) * 100;

        if ($percentageChange > 0) {
            return sprintf('+%.1f%%', $percentageChange);
        } elseif ($percentageChange < 0) {
            return sprintf('%.1f%%', $percentageChange);
        } else {
            return '0.0%';
        }
    }
}
