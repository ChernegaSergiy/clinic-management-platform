<?php

namespace App\Module\Dashboard\Service;

use App\Module\Admin\Repository\KpiRepository;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\User\Repository\UserRepository; // Import UserRepository
use DateTime;

class KpiCalculatorService
{
    private KpiRepository $kpiRepository;
    private AppointmentRepository $appointmentRepository;
    private InvoiceRepository $invoiceRepository;
    private UserRepository $userRepository; // Add UserRepository

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->invoiceRepository = new InvoiceRepository();
        $this->userRepository = new UserRepository(); // Instantiate UserRepository
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
                case 'doctor_utilization':
                    $value = $this->calculateDoctorUtilization($today);
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
        return $this->appointmentRepository->countAppointmentsByDate($date);
    }

    private function calculateRevenueGenerated(string $date): float
    {
        return $this->invoiceRepository->sumTotalAmountByDate($date);
    }

    private function calculateDoctorUtilization(string $date): float
    {
        $doctors = $this->userRepository->findAllDoctors();
        $doctorCount = count($doctors);
        if ($doctorCount === 0) {
            return 0.0;
        }

        $bookedDurations = $this->appointmentRepository->getSumOfCompletedAppointmentDurationsForDate($date); // keyed by doctor_id

        $totalUtilization = 0;
        $workdaySeconds = 8 * 3600; // Assuming 8-hour workday

        foreach ($doctors as $doctor) {
            $bookedSeconds = $bookedDurations[$doctor['id']] ?? 0;
            $doctorUtilization = ($bookedSeconds / $workdaySeconds) * 100;
            $totalUtilization += $doctorUtilization;
        }

        return $totalUtilization / $doctorCount; // Average utilization
    }
}
