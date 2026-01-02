<?php

namespace App\Module\Schedule\Service;

use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Schedule\Repository\DoctorScheduleRepository;
use App\Module\Schedule\Repository\ScheduleExceptionRepository;
use App\Module\Billing\Repository\ServiceRepository; // Assuming this exists or will be created
use DateTime;
use DateInterval;

class SchedulingService
{
    private DoctorScheduleRepository $doctorScheduleRepository;
    private ScheduleExceptionRepository $scheduleExceptionRepository;
    private AppointmentRepository $appointmentRepository;
    private ServiceRepository $serviceRepository; // Assuming ServiceRepository is in App\Module\Billing\Repository

    public function __construct(
        DoctorScheduleRepository $doctorScheduleRepository,
        ScheduleExceptionRepository $scheduleExceptionRepository,
        AppointmentRepository $appointmentRepository,
        ServiceRepository $serviceRepository
    ) {
        $this->doctorScheduleRepository = $doctorScheduleRepository;
        $this->scheduleExceptionRepository = $scheduleExceptionRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Generates time slots for a given doctor on a specific date.
     *
     * @param int $doctorId
     * @param DateTime $date
     * @param int $serviceId Optional, to get specific duration. Default to 30 mins if not provided.
     * @return array An array of time slots with availability info ['time' => DateTime, 'available' => bool, 'booked' => bool].
     */
    public function getAvailableTimeSlots(int $doctorId, DateTime $date, ?int $serviceId = null): array
    {
        $availableSlots = [];
        $dayOfWeek = (int)$date->format('w'); // 0 for Sunday, 6 for Saturday

        // 1. Get default schedule for the day
        $defaultSchedule = $this->doctorScheduleRepository->findByDoctorAndDay($doctorId, $dayOfWeek);

        if (!$defaultSchedule || !$defaultSchedule['is_available']) {
            return []; // Doctor is not available on this day by default
        }

        $scheduleStartTime = new DateTime($date->format('Y-m-d') . ' ' . $defaultSchedule['start_time']);
        $scheduleEndTime = new DateTime($date->format('Y-m-d') . ' ' . $defaultSchedule['end_time']);

        // Determine slot duration
        $slotDurationMinutes = 30; // Default
        if ($serviceId !== null) {
            $service = $this->serviceRepository->findById($serviceId);
            if ($service && isset($service['duration_minutes'])) {
                $slotDurationMinutes = (int)$service['duration_minutes'];
            }
        }
        $slotInterval = new DateInterval('PT' . $slotDurationMinutes . 'M');

        // 2. Adjust schedule based on exceptions
        $exceptions = $this->scheduleExceptionRepository->findByDoctorAndDate($doctorId, $date->format('Y-m-d'));

        $workingPeriods = [[$scheduleStartTime, $scheduleEndTime]]; // Start with default working period

        foreach ($exceptions as $exception) {
            $exceptionStartTime = new DateTime($date->format('Y-m-d') . ' ' . $exception['start_time']);
            $exceptionEndTime = new DateTime($date->format('Y-m-d') . ' ' . $exception['end_time']);

            if (!$exception['is_available']) {
                // If exception is unavailable, remove this time from working periods
                $newWorkingPeriods = [];
                foreach ($workingPeriods as $period) {
                    // Check for overlap and split/remove periods
                    // This is a simplified overlap detection. A more robust solution would be needed for complex overlaps.
                    if ($period[0] < $exceptionEndTime && $period[1] > $exceptionStartTime) {
                        // Overlap exists
                        if ($period[0] < $exceptionStartTime) {
                            $newWorkingPeriods[] = [$period[0], $exceptionStartTime];
                        }
                        if ($period[1] > $exceptionEndTime) {
                            $newWorkingPeriods[] = [$exceptionEndTime, $period[1]];
                        }
                    } else {
                        $newWorkingPeriods[] = $period;
                    }
                }
                $workingPeriods = $newWorkingPeriods;
            } else {
                // If exception makes time available, add it (this is more complex, might need merging periods)
                // For now, let's assume is_available exceptions fill gaps or extend existing periods.
                // This logic might need refinement based on exact requirements.
                // For simplicity, this example only handles 'unavailable' exceptions correctly.
            }
        }

        // 3. Generate slots from working periods
        $allSlots = [];
        foreach ($workingPeriods as $period) {
            $currentSlotStart = $period[0];
            $periodEnd = $period[1];

            while ($currentSlotStart < $periodEnd) {
                $slotEnd = clone $currentSlotStart;
                $slotEnd->add($slotInterval);

                if ($slotEnd <= $periodEnd) {
                    $allSlots[] = $currentSlotStart;
                }
                $currentSlotStart = $slotEnd;
            }
        }

        // 4. Check if slots are in the past
        $now = new DateTime();
        
        // 5. Check which slots conflict with existing appointments
        // This findByDoctorAndDate method might not exist in the AppointmentRepository yet.
        // I will assume it returns appointments for the given doctor and date.
        $bookedAppointments = $this->appointmentRepository->findByDoctorIdAndDateRange(
            $doctorId,
            $date->format('Y-m-d 00:00:00'),
            $date->format('Y-m-d 23:59:59')
        );

        $finalSlots = [];
        foreach ($allSlots as $slot) {
            $isInPast = $slot < $now;
            
            $isBooked = false;
            foreach ($bookedAppointments as $appointment) {
                $appointmentStart = new DateTime($appointment['start_time']);
                $appointmentEnd = new DateTime($appointment['end_time']);
                $slotEnd = clone $slot;
                $slotEnd->add($slotInterval);

                // Check for overlap: [slot, slotEnd) overlaps with [appointmentStart, appointmentEnd)
                if ($slot < $appointmentEnd && $slotEnd > $appointmentStart) {
                    $isBooked = true;
                    break;
                }
            }
            
            $isAvailable = !$isInPast && !$isBooked;
            
            $finalSlots[] = [
                'time' => $slot,
                'available' => $isAvailable,
                'is_in_past' => $isInPast,
                'is_booked' => $isBooked
            ];
        }

        return $finalSlots;
    }

    // You might also add methods like:
    // public function bookAppointment(int $doctorId, int $patientId, DateTime $startTime, int $serviceId): bool;
    // public function cancelAppointment(int $appointmentId): bool;
}
