<?php

namespace App\Bundles\DashboardBundle\Service;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\UserBundle\Repository\UserRepositoryInterface;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\Kpi\Repository\KpiRepository;
use App\Module\MedicalRecord\Repository\MedicalRecordRepositoryInterface;
use DateTimeImmutable;

class KpiCalculatorService
{
    private KpiRepository $kpiRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private InvoiceRepository $invoiceRepository;
    /** @phpstan-ignore property.onlyWritten */
    private UserRepositoryInterface $userRepository;
    /** @phpstan-ignore property.onlyWritten */
    private MedicalRecordRepositoryInterface $medicalRecordRepository;

    public function __construct(
        KpiRepository $kpiRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        InvoiceRepository $invoiceRepository,
        UserRepositoryInterface $userRepository,
        MedicalRecordRepositoryInterface $medicalRecordRepository
    ) {
        $this->kpiRepository = $kpiRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->userRepository = $userRepository;
        $this->medicalRecordRepository = $medicalRecordRepository;
    }

    public function calculateAndStoreAll(?string $forDate = null) : void
    {
        $definitions = $this->kpiRepository->findActiveKpiDefinitions();
        $today = $forDate ? new DateTimeImmutable($forDate) : new DateTimeImmutable('today');
        $userId = 1; // system user

        foreach ($definitions as $definition) {
            [$periodStart, $periodEnd] = $this->resolvePeriodRange($today, $definition['period'] ?? 'day');
            $value = $this->calculateKpiValue($definition['kpi_type'], $periodStart, $periodEnd);
            if (null === $value) {
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

    private function resolvePeriodRange(DateTimeImmutable $today, string $period) : array
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

    private function calculateKpiValue(string $type, string $from, string $to) : ?float
    {
        return match ($type) {
            'revenue_generated' => $this->invoiceRepository->sumRevenueForPeriod($from, $to),
            'appointments_count' => (float)$this->appointmentRepository->countScheduledByRange($from, $to),
            'doctor_utilization' => $this->calculateDoctorUtilization($from, $to),
            'readmission_rate' => $this->calculateReadmissionRate($from, $to),
            default => null,
        };
    }

    private function calculateDoctorUtilization(string $from, string $to) : ?float
    {
        $bookedHours = $this->appointmentRepository->sumBookedHoursByRange($from, $to);
        $doctorCount = $this->appointmentRepository->countDistinctDoctorsByRange($from, $to);

        if (0 === $doctorCount) {
            return null;
        }

        $days = (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days + 1;
        $totalCapacity = $doctorCount * 8 * $days;

        if ($totalCapacity <= 0) {
            return null;
        }

        return round(($bookedHours / $totalCapacity) * 100, 1);
    }

    private function calculateReadmissionRate(string $from, string $to) : ?float
    {
        $totalPatients = $this->appointmentRepository->countDistinctPatientsByRange($from, $to);
        if (0 === $totalPatients) {
            return null;
        }

        $readmitted = $this->appointmentRepository->countReadmittedPatients($from, $to);

        return round(($readmitted / $totalPatients) * 100, 1);
    }
}
