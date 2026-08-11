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

namespace App\Domain\Dashboard;

use App\Domain\Appointment\AppointmentRepository;
use App\Domain\Billing\InvoiceRepository;
use App\Domain\Kpi\KpiRepository;
use App\Domain\Kpi\KpiResultRepository;
use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepository;
use App\Bundles\UserBundle\Repository\UserRepository;
use DateTimeImmutable;

class KpiCalculatorService
{
    private KpiRepository $kpiRepository;
    private KpiResultRepository $kpiResultRepository;
    private AppointmentRepository $appointmentRepository;
    private InvoiceRepository $invoiceRepository;
    /** @phpstan-ignore property.onlyWritten */
    private UserRepository $userRepository;
    /** @phpstan-ignore property.onlyWritten */
    private MedicalRecordRepository $medicalRecordRepository;

    public function __construct(
        KpiRepository $kpiRepository,
        KpiResultRepository $kpiResultRepository,
        AppointmentRepository $appointmentRepository,
        InvoiceRepository $invoiceRepository,
        UserRepository $userRepository,
        MedicalRecordRepository $medicalRecordRepository
    ) {
        $this->kpiRepository = $kpiRepository;
        $this->kpiResultRepository = $kpiResultRepository;
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
            $this->kpiResultRepository->save([
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
