<?php

namespace App\Module\Dashboard\Service;

use App\Module\Admin\Repository\KpiRepository;
use DateTime;

class DashboardService
{
    private KpiRepository $kpiRepository;

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
    }

    /**
     * Fetches the latest KPI results for display on the dashboard.
     *
     * @return array An associative array of KPI results, keyed by kpi_type.
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
        return $dashboardKpis;
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
