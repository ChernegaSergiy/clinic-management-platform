<?php

namespace App\Module\Schedule\Controller;

use App\Core\AuthGuard;
use App\Core\View;
use App\Module\Schedule\Repository\DoctorScheduleRepository;
use App\Module\Schedule\Repository\ScheduleExceptionRepository;

class ScheduleController
{
    private DoctorScheduleRepository $doctorScheduleRepository;
    private ScheduleExceptionRepository $scheduleExceptionRepository;
    private View $view;

    public function __construct()
    {
        AuthGuard::check(); // Protect all actions in this controller
        $this->doctorScheduleRepository = new DoctorScheduleRepository();
        $this->scheduleExceptionRepository = new ScheduleExceptionRepository();
        $this->view = new View();
    }

    public function index(): void
    {
        // For now, only for the logged-in doctor.
        // Later, could be extended for admins to manage any doctor.
        $doctorId = (int)$_SESSION['user']['id'];

        $schedule = $this->doctorScheduleRepository->findByDoctor($doctorId);
        
        // Fetch exceptions as well
        $exceptions = $this->scheduleExceptionRepository->findByDoctorAndDateRange(
            $doctorId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 year')) // Fetch for the next year
        );

        // Re-organize schedule by day_of_week for easier access in the view
        $scheduleByDay = [];
        foreach ($schedule as $entry) {
            $scheduleByDay[$entry['day_of_week']] = $entry;
        }

        View::render('@modules/Schedule/templates/index.html.twig', [
            'scheduleByDay' => $scheduleByDay,
            'exceptions' => $exceptions,
        ]);
    }

    // update(), addException(), deleteException() methods will be added later.
    public function update(): void
    {
        $doctorId = (int)$_SESSION['user']['id'];
        $scheduleData = $_POST['schedule'] ?? [];

        foreach ($scheduleData as $dayOfWeek => $data) {
            $existingSchedule = $this->doctorScheduleRepository->findByDoctorAndDay($doctorId, (int)$dayOfWeek);

            $scheduleEntry = [
                'doctor_id' => $doctorId,
                'day_of_week' => (int)$dayOfWeek,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'is_available' => isset($data['is_available']) ? 1 : 0,
            ];

            if ($existingSchedule) {
                $this->doctorScheduleRepository->update($existingSchedule['id'], $scheduleEntry);
            } else {
                $this->doctorScheduleRepository->create($scheduleEntry);
            }
        }
        
        // TODO: Add flash message for success
        header('Location: /doctor/schedule');
        exit;
    }

    public function addException(): void
    {
        $doctorId = (int)$_SESSION['user']['id'];
        $exceptionData = [
            'doctor_id' => $doctorId,
            'exception_date' => $_POST['exception_date'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'is_available' => (int)$_POST['is_available'],
            'notes' => $_POST['notes'] ?? null,
        ];

        $this->scheduleExceptionRepository->create($exceptionData);

        // TODO: Add flash message
        header('Location: /doctor/schedule');
        exit;
    }

    public function deleteException(): void
    {
        $doctorId = (int)$_SESSION['user']['id'];
        $exceptionId = (int)$_POST['exception_id'];

        $exception = $this->scheduleExceptionRepository->findById($exceptionId);

        if ($exception && (int)$exception['doctor_id'] === $doctorId) {
            $this->scheduleExceptionRepository->delete($exceptionId);
            // TODO: Add flash message
        } else {
            // TODO: Add error flash message (permission denied)
        }

        header('Location: /doctor/schedule');
        exit;
    }
}
