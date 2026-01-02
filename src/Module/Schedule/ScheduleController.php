<?php

namespace App\Module\Schedule;

use App\Core\AuthGuard;
use App\Core\View;
use App\Core\Gate; // Added
use App\Module\Schedule\Repository\DoctorScheduleRepository;
use App\Module\Schedule\Repository\ScheduleExceptionRepository;
use App\Module\User\Repository\UserRepository; // Added

class ScheduleController
{
    private DoctorScheduleRepository $doctorScheduleRepository;
    private ScheduleExceptionRepository $scheduleExceptionRepository;
    private UserRepository $userRepository; // Added
    private View $view;

    public function __construct()
    {
        AuthGuard::check(); // Protect all actions in this controller
        $this->doctorScheduleRepository = new DoctorScheduleRepository();
        $this->scheduleExceptionRepository = new ScheduleExceptionRepository();
        $this->userRepository = new UserRepository(); // Added
        $this->view = new View();
    }

    public function index(): void
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $canManageAllSchedules = Gate::allows('admin.manage_schedules'); // Assuming this permission is defined

        $targetDoctorId = $sessionUserId; // Default to own schedule
        $allDoctors = [];

        if ($canManageAllSchedules) {
            $allDoctors = $this->userRepository->findAllDoctors();
            if (isset($_GET['doctor_id']) && (int)$_GET['doctor_id'] > 0) {
                // Validate if selected doctor is actually a doctor
                $selectedDoctor = $this->userRepository->findById((int)$_GET['doctor_id']);
                if ($selectedDoctor && $selectedDoctor['role_id'] == $this->userRepository->findRoleIdByName('doctor')) {
                    $targetDoctorId = (int)$_GET['doctor_id'];
                } else {
                    // If selected user is not a doctor, default back to current user's schedule or the first doctor
                    // For now, let's default to current user's schedule to prevent confusion
                    $targetDoctorId = $sessionUserId;
                }
            }
        } else {
            // Non-admins can only view/manage their own schedule
            $targetDoctorId = $sessionUserId;
        }


        $schedule = $this->doctorScheduleRepository->findByDoctor($targetDoctorId);
        
        $exceptions = $this->scheduleExceptionRepository->findByDoctorAndDateRange(
            $targetDoctorId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 year'))
        );

        $scheduleByDay = [];
        foreach ($schedule as $entry) {
            $scheduleByDay[$entry['day_of_week']] = $entry;
        }

        // Debug: Log the target doctor ID
        error_log("Schedule Debug: sessionUserId=$sessionUserId, targetDoctorId=$targetDoctorId, canManageAllSchedules=" . ($canManageAllSchedules ? 'true' : 'false'));
        
        View::render('@modules/Schedule/templates/index.html.twig', [
            'scheduleByDay' => $scheduleByDay,
            'exceptions' => $exceptions,
            'allDoctors' => $allDoctors, // Pass all doctors if admin
            'selectedDoctorId' => $targetDoctorId,
            'canManageAllSchedules' => $canManageAllSchedules,
        ]);
    }

    public function update(): void
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $targetDoctorId = $sessionUserId; // Default

        if (Gate::allows('admin.manage_schedules') && isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] > 0) {
            $targetDoctorId = (int)$_POST['doctor_id'];
        }

        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== $sessionUserId) {
            Gate::authorize('admin.manage_schedules');
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
        header('Location: /doctor/schedule' . ($targetDoctorId !== $sessionUserId ? '?doctor_id=' . $targetDoctorId : ''));
        exit;
    }

    public function addException(): void
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $targetDoctorId = $sessionUserId; // Default

        if (Gate::allows('admin.manage_schedules') && isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] > 0) {
            $targetDoctorId = (int)$_POST['doctor_id'];
        }

        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== $sessionUserId) {
            Gate::authorize('admin.manage_schedules');
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
        header('Location: /doctor/schedule' . ($targetDoctorId !== $sessionUserId ? '?doctor_id=' . $targetDoctorId : ''));
        exit;
    }

    public function deleteException(): void
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $exceptionId = (int)$_POST['exception_id'];

        $exception = $this->scheduleExceptionRepository->findById($exceptionId);

        if (!$exception) {
            // TODO: Add error flash message (exception not found)
            header('Location: /doctor/schedule'); // Redirect even if not found
            exit;
        }

        $targetDoctorId = (int)$exception['doctor_id'];

        // Authorize if attempting to delete another user's exception
        if ($targetDoctorId !== $sessionUserId) {
            Gate::authorize('admin.manage_schedules');
        }
        
        // Final check that the exception belongs to the intended targetDoctorId
        if ($targetDoctorId === $sessionUserId || Gate::allows('admin.manage_schedules')) { // Double check for safety
             $this->scheduleExceptionRepository->delete($exceptionId);
            // TODO: Add flash message
        } else {
            // TODO: Add error flash message (permission denied)
        }

        header('Location: /doctor/schedule' . ($targetDoctorId !== $sessionUserId ? '?doctor_id=' . $targetDoctorId : ''));
        exit;
    }
}
