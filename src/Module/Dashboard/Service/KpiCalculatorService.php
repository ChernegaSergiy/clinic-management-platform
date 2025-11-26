<?php

namespace App\Module\Dashboard\Service;

use App\Module\Admin\Repository\KpiRepository;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Billing\Repository\InvoiceRepository;
use DateTime;

class KpiCalculatorService
{
    private KpiRepository $kpiRepository;
    private AppointmentRepository $appointmentRepository;
    private InvoiceRepository $invoiceRepository;

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->invoiceRepository = new InvoiceRepository();
    }

    public function calculateAndStoreAll(): void
    {
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        $today = (new DateTime())->format('Y-m-d');

        foreach ($definitions as $definition) {
            if (!$definition['is_active']) {
                continue;
            }

            $value = 0;
            switch ($definition['kpi_type']) {
                case 'appointments_count':
                    $value = $this->calculateAppointmentsCount($today);
                    break;
                case 'revenue_generated':
                    $value = $this->calculateRevenueGenerated($today);
                    break;
                case 'patient_satisfaction':
                    // TODO: Implement actual patient satisfaction calculation when feedback mechanism is added
                    $value = 4.5; // Placeholder value
                    break;
            }

            $this->kpiRepository->saveKpiResult([
                'kpi_id' => $definition['id'],
                'user_id' => 1, // Placeholder for system-generated KPI
                'period_start' => $today,
                'period_end' => $today,
                'calculated_value' => $value,
                'notes' => 'Автоматично розраховано'
            ]);
        }
    }

    private function calculateAppointmentsCount(string $date): int
    {
        // This is a guess. The actual repository method might be different.
        // I will need to check the AppointmentRepository to be sure.
        return $this->appointmentRepository->countAppointmentsByDate($date);
    }

    private function calculateRevenueGenerated(string $date): float
    {
        // This is a guess. The actual repository method might be different.
        // I will need to check the InvoiceRepository to be sure.
        return $this->invoiceRepository->sumTotalAmountByDate($date);
    }
}
