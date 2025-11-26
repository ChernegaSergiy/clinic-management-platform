<?php

namespace App\Module\Dashboard\Service;

use App\Module\Admin\Repository\KpiRepository;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\User\Repository\UserRepository;
use App\Module\MedicalRecord\Repository\MedicalRecordRepository; // Import MedicalRecordRepository
use DateTime;

class KpiCalculatorService
{
    private KpiRepository $kpiRepository;
    private AppointmentRepository $appointmentRepository;
    private InvoiceRepository $invoiceRepository;
    private UserRepository $userRepository;
    private MedicalRecordRepository $medicalRecordRepository; // Add MedicalRecordRepository

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->invoiceRepository = new InvoiceRepository();
        $this->userRepository = new UserRepository();
        $this->medicalRecordRepository = new MedicalRecordRepository(); // Instantiate MedicalRecordRepository
    }

    public function calculateAndStoreAll(?string $forDate = null): void
    {
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        $today = $forDate ?? (new DateTime())->format('Y-m-d');

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
                case 'readmission_rate':
                    $value = $this->calculateReadmissionRate($today);
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

    private function calculateReadmissionRate(string $date): float
    {
        $dischargeEvents = $this->appointmentRepository->getCompletedAppointmentsWithIcdCodes($date);

        $totalDischarges = count($dischargeEvents);
        if ($totalDischarges === 0) {
            return 0.0;
        }

        $readmissions = 0;
        $readmissionTimeframeDays = 30; // Define readmission timeframe

        foreach ($dischargeEvents as $event) {
            $patientId = $event['patient_id'];
            $dischargeDate = (new DateTime($date))->format('Y-m-d H:i:s'); // Use end of day for search
            $originalIcdCodeIds = explode(',', $event['icd_code_ids']);
            $originalIcdCodeIds = array_filter($originalIcdCodeIds); // Remove empty values

            if (empty($originalIcdCodeIds)) {
                continue; // Cannot track readmission without an original diagnosis
            }

            // Find subsequent appointments for the same patient within the timeframe
            $subsequentAppointments = $this->appointmentRepository->findPatientSubsequentAppointments(
                $patientId,
                $dischargeDate,
                $readmissionTimeframeDays
            );

            foreach ($subsequentAppointments as $subsequentAppt) {
                // Get ICD codes for the subsequent appointment
                $subsequentMedicalRecordId = $subsequentAppt['medical_record_id'];
                if (!$subsequentMedicalRecordId) {
                    continue; // Subsequent appointment has no medical record, can't check ICDs
                }
                $subsequentIcdCodes = $this->medicalRecordRepository->getIcdCodesForMedicalRecord($subsequentMedicalRecordId);
                $subsequentIcdCodeIds = array_column($subsequentIcdCodes, 'id');

                // Check for overlapping ICD codes
                if (array_intersect($originalIcdCodeIds, $subsequentIcdCodeIds)) {
                    $readmissions++;
                    break; // Count only one readmission per discharge event
                }
            }
        }

        return ($readmissions / $totalDischarges) * 100;
    }
}
