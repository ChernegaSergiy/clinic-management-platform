<?php

namespace App\Module\Schedule;

use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Module\Schedule\Repository\DoctorScheduleRepository;
use App\Module\Schedule\Repository\ScheduleExceptionRepository;
use App\Module\User\Repository\UserRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class ScheduleController
{
    private DoctorScheduleRepository $doctorScheduleRepository;
    private ScheduleExceptionRepository $scheduleExceptionRepository;
    private UserRepositoryInterface $userRepository;

    public function __construct(DoctorScheduleRepository $doctorScheduleRepository, ScheduleExceptionRepository $scheduleExceptionRepository, UserRepositoryInterface $userRepository)
    {
        $this->doctorScheduleRepository = $doctorScheduleRepository;
        $this->scheduleExceptionRepository = $scheduleExceptionRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/doctor/schedule', name: 'doctor_schedule_index', methods: ['GET'])]
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

    #[Route('/admin/schedules', name: 'admin_schedules_index', methods: ['GET'])]
    public function adminIndex(): void
    {
        // Admin schedule management for all doctors
        Gate::authorize('schedules.manage_all');

        $allDoctors = $this->userRepository->findAllDoctors();

        View::render('@modules/Schedule/templates/admin.html.twig', [
            'allDoctors' => $allDoctors,
        ]);
    }

    #[Route('/admin/schedules/show', name: 'admin_schedules_show', methods: ['GET'])]
    public function adminShow(): void
    {
        Gate::authorize('schedules.manage_all');

        $doctorId = (int)($_GET['id'] ?? 0);
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

        View::render('@modules/Schedule/templates/show.html.twig', [
            'doctor' => $doctor,
            'scheduleByDay' => $scheduleByDay
        ]);
    }

    #[Route('/doctor/schedule/update', name: 'doctor_schedule_update', methods: ['POST'])]
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

    #[Route('/doctor/schedule/exceptions/add', name: 'doctor_schedule_exceptions_add', methods: ['POST'])]
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

    #[Route('/doctor/schedule/exceptions/delete', name: 'doctor_schedule_exceptions_delete', methods: ['POST'])]
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
    #[Route('/admin/schedules/update', name: 'admin_schedules_update', methods: ['POST'])]
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

        header('Location: /admin/schedules/show?id=' . $targetDoctorId);
        exit;
    }

    #[Route('/admin/schedules/exceptions/add', name: 'admin_schedules_exceptions_add', methods: ['POST'])]
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

    #[Route('/admin/schedules/exceptions/delete', name: 'admin_schedules_exceptions_delete', methods: ['POST'])]
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

    #[Route('/admin/schedules/edit', name: 'admin_schedules_edit', methods: ['GET'])]
    public function adminEdit(): void
    {
        Gate::authorize('schedules.manage_all');

        $doctorId = (int)($_GET['id'] ?? 0);
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
