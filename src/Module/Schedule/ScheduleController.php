<?php

namespace App\Module\Schedule;

use App\Core\AuthGuard;
use App\Core\View;
use App\Core\Gate;
use App\Module\Schedule\Repository\DoctorScheduleRepository;
use App\Module\Schedule\Repository\ScheduleExceptionRepository;
use App\Module\User\Repository\UserRepository;

class ScheduleController
{
    private DoctorScheduleRepository $doctorScheduleRepository;
    private ScheduleExceptionRepository $scheduleExceptionRepository;
    private UserRepository $userRepository;
    private View $view;

    public function __construct()
    {
        AuthGuard::check(); // Protect all actions in this controller
        $this->doctorScheduleRepository = new DoctorScheduleRepository();
        $this->scheduleExceptionRepository = new ScheduleExceptionRepository();
        $this->userRepository = new UserRepository();
        $this->view = new View();
    }

    public function index(): void
    {
        // Personal schedule for doctors
        Gate::authorize('schedules.manage_own');
        
        $userId = (int)$_SESSION['user']['id'];
        $schedule = $this->doctorScheduleRepository->findByDoctor($userId);
        $exceptions = $this->scheduleExceptionRepository->findByDoctorAndDateRange(
            $userId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 year'))
        );

        $scheduleByDay = [];
        foreach ($schedule as $entry) {
            $scheduleByDay[$entry['day_of_week']] = $entry;
        }
        
        View::render('@modules/Schedule/templates/personal.html.twig', [
            'scheduleByDay' => $scheduleByDay,
            'exceptions' => $exceptions,
        ]);
    }

    public function adminIndex(): void
    {
        // Admin schedule management for all doctors
        Gate::authorize('schedules.manage_all');
        
        $selectedDoctorId = null;
        $selectedDoctorName = null;
        $selectedDoctorSchedule = [];
        $mode = $_GET['mode'] ?? 'view';
        
        $allDoctors = $this->userRepository->findAllDoctors();
        $allSchedules = [];
        
        foreach ($allDoctors as $doctor) {
            $schedules = $this->doctorScheduleRepository->findByDoctor($doctor['id']);
            $scheduleByDay = [];
            foreach ($schedules as $entry) {
                $scheduleByDay[$entry['day_of_week']] = $entry;
            }
            
            $doctorSchedule = [
                'doctor' => $doctor,
                'scheduleByDay' => $scheduleByDay
            ];
            
            $allSchedules[] = $doctorSchedule;
        }
        
        // If specific doctor is selected, prepare their schedule for editing
        if (isset($_GET['doctor_id']) && (int)$_GET['doctor_id'] > 0) {
            $selectedDoctorId = (int)$_GET['doctor_id'];
            $selectedDoctor = $this->userRepository->findById($selectedDoctorId);
            $selectedDoctorName = $selectedDoctor['full_name'] ?? null;
            
            if ($selectedDoctor) {
                $schedules = $this->doctorScheduleRepository->findByDoctor($selectedDoctorId);
                foreach ($schedules as $entry) {
                    $selectedDoctorSchedule[$entry['day_of_week']] = $entry;
                }
            }
        }
        
        View::render('@modules/Schedule/templates/admin.html.twig', [
            'allSchedules' => $allSchedules,
            'allDoctors' => $allDoctors,
            'selectedDoctorId' => $selectedDoctorId,
            'selectedDoctorName' => $selectedDoctorName,
            'selectedDoctorSchedule' => $selectedDoctorSchedule,
            'mode' => $mode
        ]);
    }

    public function update(): void
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $targetDoctorId = $sessionUserId; // Default

        if (Gate::allows('schedules.manage_all') && isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] > 0) {
            $targetDoctorId = (int)$_POST['doctor_id'];
        }

        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== $sessionUserId) {
            Gate::authorize('schedules.manage_all');
        }

        $scheduleData = $_POST['schedule'] ?? [];

        foreach ($scheduleData as $dayOfWeek => $data) {
            $existingSchedule = $this->doctorScheduleRepository->findByDoctorAndDay($targetDoctorId, (int)$dayOfWeek);

            $scheduleEntry = [
                'doctor_id' => $targetDoctorId,
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
        $redirectUrl = ($targetDoctorId !== $sessionUserId) ? '/admin/schedules' : '/doctor/schedule';
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function addException(): void
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $targetDoctorId = $sessionUserId; // Default

        if (Gate::allows('schedules.manage_all') && isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] > 0) {
            $targetDoctorId = (int)$_POST['doctor_id'];
        }

        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== $sessionUserId) {
            Gate::authorize('schedules.manage_all');
        }

        $exceptionData = [
            'doctor_id' => $targetDoctorId,
            'exception_date' => $_POST['exception_date'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'is_available' => (int)$_POST['is_available'],
            'notes' => $_POST['notes'] ?? null,
        ];

        $this->scheduleExceptionRepository->create($exceptionData);

        // TODO: Add flash message
        $redirectUrl = ($targetDoctorId !== $sessionUserId) ? '/admin/schedules' : '/doctor/schedule';
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function deleteException(): void
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $exceptionId = (int)$_POST['exception_id'];

        $exception = $this->scheduleExceptionRepository->findById($exceptionId);

        if (!$exception) {
            // TODO: Add error flash message (exception not found)
            $redirectUrl = '/doctor/schedule';
            header('Location: ' . $redirectUrl);
            exit;
        }

        $targetDoctorId = (int)$exception['doctor_id'];

        // Authorize if attempting to delete another user's exception
        if ($targetDoctorId !== $sessionUserId) {
            Gate::authorize('schedules.manage_all');
        }
        
        // Final check that exception belongs to the intended targetDoctorId
        if ($targetDoctorId === $sessionUserId || Gate::allows('schedules.manage_all')) { // Double check for safety
             $this->scheduleExceptionRepository->delete($exceptionId);
            // TODO: Add flash message
        } else {
            // TODO: Add error flash message (permission denied)
        }

        $redirectUrl = ($targetDoctorId !== $sessionUserId) ? '/admin/schedules' : '/doctor/schedule';
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Admin schedule management methods
    public function adminUpdate(): void
    {
        Gate::authorize('schedules.manage_all');
        
        $targetDoctorId = (int)$_POST['doctor_id'];
        
        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== (int)$_SESSION['user']['id']) {
            Gate::authorize('schedules.manage_all');
        }

        $scheduleData = $_POST['schedule'] ?? [];

        foreach ($scheduleData as $dayOfWeek => $data) {
            $existingSchedule = $this->doctorScheduleRepository->findByDoctorAndDay($targetDoctorId, (int)$dayOfWeek);

            $scheduleEntry = [
                'doctor_id' => $targetDoctorId,
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
        
        header('Location: /admin/schedules');
        exit;
    }

    public function adminAddException(): void
    {
        Gate::authorize('schedules.manage_all');
        
        $targetDoctorId = (int)$_POST['doctor_id'];
        
        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== (int)$_SESSION['user']['id']) {
            Gate::authorize('schedules.manage_all');
        }

        $exceptionData = [
            'doctor_id' => $targetDoctorId,
            'exception_date' => $_POST['exception_date'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'is_available' => (int)$_POST['is_available'],
            'notes' => $_POST['notes'] ?? null,
        ];

        $this->scheduleExceptionRepository->create($exceptionData);

        header('Location: /admin/schedules');
        exit;
    }

    public function adminDeleteException(): void
    {
        Gate::authorize('schedules.manage_all');
        
        $exceptionId = (int)$_POST['exception_id'];

        $exception = $this->scheduleExceptionRepository->findById($exceptionId);

        if (!$exception) {
            header('Location: /admin/schedules');
            exit;
        }

        $targetDoctorId = (int)$exception['doctor_id'];

        // Authorize if attempting to delete another user's exception
        if ($targetDoctorId !== (int)$_SESSION['user']['id']) {
            Gate::authorize('schedules.manage_all');
        }
        
        // Final check that exception belongs to the intended targetDoctorId
        if ($targetDoctorId === (int)$_SESSION['user']['id'] || Gate::allows('schedules.manage_all')) { // Double check for safety
             $this->scheduleExceptionRepository->delete($exceptionId);
        }
        
        header('Location: /admin/schedules');
        exit;
    }

    public function adminEdit($id): void
    {
        Gate::authorize('schedules.manage_all');
        
        $doctorId = (int)$id;
        $doctor = $this->userRepository->findById($doctorId);
        
        if (!$doctor) {
            header('Location: /admin/schedules');
            exit;
        }
        
        $schedules = $this->doctorScheduleRepository->findByDoctor($doctorId);
        $scheduleByDay = [];
        foreach ($schedules as $entry) {
            $scheduleByDay[$entry['day_of_week']] = $entry;
        }
        
        View::render('@modules/Schedule/templates/edit.html.twig', [
            'doctor' => $doctor,
            'scheduleByDay' => $scheduleByDay
        ]);
    }
}