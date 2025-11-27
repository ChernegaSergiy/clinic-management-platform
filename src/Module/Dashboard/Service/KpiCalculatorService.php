<?php

namespace App\Module\Dashboard\Service;

use App\Module\Admin\Repository\KpiRepository;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\User\Repository\UserRepository;
use App\Module\MedicalRecord\Repository\MedicalRecordRepository;
use DateTimeImmutable;

class KpiCalculatorService
{
    private KpiRepository $kpiRepository;
    private AppointmentRepository $appointmentRepository;
    private InvoiceRepository $invoiceRepository;
    private UserRepository $userRepository;
    private MedicalRecordRepository $medicalRecordRepository;

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->invoiceRepository = new InvoiceRepository();
        $this->userRepository = new UserRepository();
        $this->medicalRecordRepository = new MedicalRecordRepository();
    }

    public function calculateAndStoreAll(?string $forDate = null): void
    {
        $definitions = $this->kpiRepository->findActiveKpiDefinitions();
        $today = $forDate ? new DateTimeImmutable($forDate) : new DateTimeImmutable('today');
        $userId = 1; // system user

        foreach ($definitions as $definition) {
            [$periodStart, $periodEnd] = $this->resolvePeriodRange($today, $definition['period'] ?? 'day');
            $value = $this->calculateKpiValue($definition['kpi_type'], $periodStart, $periodEnd);
            if ($value === null) {
                continue;
            }
            $this->kpiRepository->saveKpiResult([
                'kpi_id' => $definition['id'],
                'user_id' => $userId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'calculated_value' => $value,
                'notes' => sprintf('CLI auto-calculated %s-%s', $periodStart, $periodEnd),
            ]);
        }
    }

    private function resolvePeriodRange(DateTimeImmutable $today, string $period): array
    {
        return match ($period) {
            'week' => [
                $today->modify('-6 days')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            'month' => [
                $today->modify('-29 days')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            default => [
                $today->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
        };
    }

    private function calculateKpiValue(string $type, string $from, string $to): ?float
    {
        return match ($type) {
            'revenue_generated' => $this->invoiceRepository->sumRevenueForPeriod($from, $to),
            'appointments_count' => (float)$this->appointmentRepository->countScheduledByRange($from, $to),
            'doctor_utilization' => $this->calculateDoctorUtilization($from, $to),
            'readmission_rate' => $this->calculateReadmissionRate($from, $to),
            default => null,
        };
    }

    private function calculateDoctorUtilization(string $from, string $to): ?float
    {
        $bookedHours = $this->appointmentRepository->sumBookedHoursByRange($from, $to);
        $doctorCount = $this->appointmentRepository->countDistinctDoctorsByRange($from, $to);

        if ($doctorCount === 0) {
            return null;
        }

        $days = (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days + 1;
        $totalCapacity = $doctorCount * 8 * $days;

        if ($totalCapacity <= 0) {
            return null;
        }

        return round(($bookedHours / $totalCapacity) * 100, 1);
    }

    private function calculateReadmissionRate(string $from, string $to): ?float
    {
        $totalPatients = $this->appointmentRepository->countDistinctPatientsByRange($from, $to);
        if ($totalPatients === 0) {
            return null;
        }

        $readmitted = $this->appointmentRepository->countReadmittedPatients($from, $to);

        return round(($readmitted / $totalPatients) * 100, 1);
    }
}
