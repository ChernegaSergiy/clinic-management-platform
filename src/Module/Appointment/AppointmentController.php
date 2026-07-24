<?php

namespace App\Module\Appointment;

use App\Database\Database;
use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Core\Service\NotificationService;
use App\Core\Validation\Validator;
use App\Module\Appointment\Repository\AppointmentRepositoryInterface;
use App\Module\Billing\Repository\ServiceRepository;
use App\Module\Patient\Repository\PatientRepositoryInterface;
use App\Module\Room\Repository\RoomRepository;
use App\Module\Schedule\Repository\DoctorScheduleRepository;
use App\Module\Schedule\Repository\ScheduleExceptionRepository;
use App\Module\Schedule\Service\SchedulingService;
use App\Module\User\Repository\UserRepositoryInterface;
use App\Module\User\Repository\UserRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class AppointmentController
{
    private AppointmentRepositoryInterface $appointmentRepository;
    private PatientRepositoryInterface $patientRepository;
    private UserRepositoryInterface $userRepository;
    private NotificationService $notificationService;
    private SchedulingService $schedulingService;
    private ServiceRepository $serviceRepository;
    private RoomRepository $roomRepository;
    private DoctorScheduleRepository $doctorScheduleRepository;
    private ScheduleExceptionRepository $scheduleExceptionRepository;

    public function __construct(
        AppointmentRepositoryInterface $appointmentRepository,
        PatientRepositoryInterface $patientRepository,
        UserRepositoryInterface $userRepository,
        NotificationService $notificationService,
        SchedulingService $schedulingService,
        DoctorScheduleRepository $doctorScheduleRepository,
        ScheduleExceptionRepository $scheduleExceptionRepository,
        ServiceRepository $serviceRepository,
        RoomRepository $roomRepository
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->patientRepository = $patientRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->schedulingService = $schedulingService;
        $this->doctorScheduleRepository = $doctorScheduleRepository;
        $this->scheduleExceptionRepository = $scheduleExceptionRepository;
        $this->serviceRepository = $serviceRepository;
        $this->roomRepository = $roomRepository;
    }

    #[Route('/appointments', name: 'appointment_index', methods: ['GET'])]
    public function index(): void
    {
        AuthGuard::check();
        $user = Gate::getUser();
        $doctors = $this->userRepository->findAllDoctors();
        $services = $this->serviceRepository->findAll();
        $waitlist = $this->appointmentRepository->getWaitlistEntries();
        $appointments = [];

        if (Gate::allows('appointment.view.any')) {
            $appointments = $this->appointmentRepository->findAll();
        } elseif (Gate::allows('appointment.view.own')) {
            if ($user && $user->getId()) {
                $appointments = $this->appointmentRepository->findByDoctorId($user->getId());
            }
        }

        $calendarDoctors = [];
        foreach ($doctors as $doctor) {
            if (
                Gate::allows('appointment.view.any') ||
                (Gate::allows('appointment.view.own') && (int)$doctor['id'] === $user->getId())
            ) {
                $calendarDoctors[] = [
                    'id' => $doctor['id'],
                    'title' => $doctor['full_name']
                ];
            }
        }

        View::render('@modules/Appointment/templates/index.html.twig', [
            'doctors' => $calendarDoctors,
            'waitlist' => $waitlist,
            'appointments' => $appointments,
        ]);
    }

    #[Route('/book-appointment', name: 'appointment_public_form', methods: ['GET'])]
    public function publicForm(): void
    {
        $doctors = $this->userRepository->findAllDoctors();
        $services = $this->serviceRepository->findAll();

        $selectedDoctorId = (int)($_GET['doctor_id'] ?? 0);
        $selectedDateStr = $_GET['date'] ?? date('Y-m-d');
        $selectedServiceId = (int)($_GET['service_id'] ?? $services[0]['id'] ?? 0);

        $availableSlots = [];
        if ($selectedDoctorId && $selectedDateStr && $selectedServiceId) {
            try {
                $selectedDate = new \DateTime($selectedDateStr);
                $availableSlots = $this->schedulingService->getAvailableTimeSlots($selectedDoctorId, $selectedDate, $selectedServiceId);
            } catch (\Exception $e) {
                // Invalid date format, handle error or ignore
            }
        }

        View::render('@modules/Appointment/templates/public/book.html.twig', [
            'doctors' => $doctors,
            'services' => $services,
            'availableSlots' => $availableSlots,
            'selectedDate' => $selectedDateStr,
            'old' => array_merge($_SESSION['old'] ?? [], $_GET),
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['public_success_message'] ?? null,
        ]);
        unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['public_success_message']);
    }

    #[Route('/book-appointment', name: 'appointment_submit_public_form', methods: ['POST'])]
    public function submitPublicForm(): void
    {
        $rawInput = $_POST;

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'phone_number' => ['required'],
            'email' => ['required', 'email'],
            'doctor_id' => ['required', 'numeric'],
            'service_id' => ['required', 'numeric'],
            'start_time' => ['required', 'datetime'],
            'end_time' => ['required', 'datetime'],
        ];

        if (!$validator->validate($rawInput, $rules)) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $rawInput;
            header('Location: /book-appointment?' . http_build_query(['doctor_id' => $rawInput['doctor_id'] ?? '', 'service_id' => $rawInput['service_id'] ?? '', 'date' => $rawInput['date'] ?? '']));
            exit();
        }

        // Advanced validation: Check if the slot is still available
        $selectedDoctorId = (int)$rawInput['doctor_id'];
        $selectedServiceId = (int)$rawInput['service_id'];
        $startTime = new \DateTime($rawInput['start_time']);

        $availableSlots = $this->schedulingService->getAvailableTimeSlots($selectedDoctorId, $startTime, $selectedServiceId);

        $isSlotAvailable = false;
        foreach ($availableSlots as $slot) {
            if ($slot['time']->format('Y-m-d H:i:s') === $startTime->format('Y-m-d H:i:s') && $slot['available']) {
                $isSlotAvailable = true;
                break;
            }
        }

        if (!$isSlotAvailable) {
            $_SESSION['errors'] = ['start_time' => ['The selected time slot is no longer available. Please choose another one.']];
            $_SESSION['old'] = $rawInput;
            header('Location: /book-appointment?' . http_build_query(['doctor_id' => $rawInput['doctor_id'], 'service_id' => $rawInput['service_id'], 'date' => $rawInput['date']]));
            exit();
        }

        // Find or create patient
        $patient = $this->patientRepository->findByEmail($rawInput['email']);
        if (!$patient) {
            $patientId = $this->patientRepository->save([
                'first_name' => $rawInput['first_name'],
                'last_name' => $rawInput['last_name'],
                'phone' => $rawInput['phone_number'],
                'email' => $rawInput['email'],
                // These are required by DB but not on the form, so use placeholders
                'birth_date' => '1900-01-01',
                'gender' => 'other',
            ]);
            if (!$patientId) {
                $_SESSION['errors'] = ['patient' => ['Could not create a new patient record.']];
                $_SESSION['old'] = $rawInput;
                header('Location: /book-appointment?' . http_build_query(['doctor_id' => $rawInput['doctor_id'], 'service_id' => $rawInput['service_id'], 'date' => $rawInput['date']]));
                exit();
            }
        } else {
            $patientId = $patient['id'];
        }

        // Add to waitlist
        $waitlistData = [
            'patient_id' => $patientId,
            'desired_doctor_id' => (int)$rawInput['doctor_id'],
            'desired_start_time' => $rawInput['start_time'],
            'contact_phone' => $rawInput['phone_number'],
            'contact_email' => $rawInput['email'],
            'notes' => $rawInput['notes'] ?? null,
        ];

        $result = $this->appointmentRepository->addToWaitlist($waitlistData);

        $_SESSION['public_success_message'] = 'Вашу заявку успішно додано до списку очікування! Ми зв\'яжемося з вами найближчим часом для підтвердження запису.';
        header('Location: /book-appointment');
        exit();
    }

    #[Route('/appointments/waitlist/reject', name: 'appointment_reject_waitlist', methods: ['POST'])]
    public function rejectWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.edit.any');
        $id = (int)($_POST['id'] ?? 0);
        $entry = $this->appointmentRepository->findWaitlistById($id);
        if (!$entry) {
            http_response_code(404);
            echo "Заявку не знайдено";
            return;
        }
        $this->appointmentRepository->updateWaitlistStatus($id, 'cancelled');
        header('Location: /appointments');
        exit();
    }

    #[Route('/appointments/new', name: 'appointment_create', methods: ['GET'])]
    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.create');

        $user = Gate::getUser();

        $patients = $this->patientRepository->findAllActive();
        $doctors = $this->userRepository->findAllDoctors();
        $services = $this->serviceRepository->findAll();
        $rooms = $this->roomRepository->findAvailable();

        $selectedDoctorId = (int)($_GET['doctor_id'] ?? 0);
        $selectedDateStr = $_GET['date'] ?? date('Y-m-d');
        $selectedServiceId = (int)($_GET['service_id'] ?? $services[0]['id'] ?? 0);

        $availableSlots = [];
        if ($selectedDoctorId && $selectedDateStr) {
            try {
                $selectedDate = new \DateTime($selectedDateStr);
                $availableSlots = $this->schedulingService->getAvailableTimeSlots($selectedDoctorId, $selectedDate, $selectedServiceId);
            } catch (\Exception $e) {
                // Invalid date format, handle error or ignore
            }
        }

        // Prefill data from waitlist if applicable
        $prefill = [];
        $waitlistId = (int)($_GET['waitlist_id'] ?? 0);
        if ($waitlistId) {
            $entry = $this->appointmentRepository->findWaitlistById($waitlistId);
            if ($entry) {
                $prefill['waitlist_id'] = $waitlistId;
                if (!empty($entry['desired_doctor_id'])) {
                    $prefill['doctor_id'] = $entry['desired_doctor_id'];
                }
                if (!empty($entry['desired_start_time'])) {
                    try {
                        $dt = $this->normalizeDateTime($entry['desired_start_time']);
                        $prefill['start_time'] = $dt->format('Y-m-d\TH:i');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            }
        }

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $doctorOptions = [];
        if ($user->hasPermission('appointment.edit.own') && !$user->hasPermission('appointment.edit.any')) {
            foreach ($doctors as $doctor) {
                if ((int)$doctor['id'] === $user->getId()) {
                    $doctorOptions[$doctor['id']] = $doctor['full_name'];
                    if (empty($prefill['doctor_id'])) {
                        $prefill['doctor_id'] = $doctor['id'];
                    }
                    break;
                }
            }
        } else {
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }
        }

        $serviceOptions = [];
        foreach ($services as $service) {
            $serviceOptions[$service['id']] = $service['name'] . ' (' . $service['duration_minutes'] . ' хв)';
        }

        $roomOptions = [];
        foreach ($rooms as $room) {
            $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
        }

        View::render('@modules/Appointment/templates/new.html.twig', [
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
            'services' => $serviceOptions,
            'servicesForJs' => $services,
            'rooms' => $roomOptions,
            'old' => array_merge($prefill, $_GET),
            'availableSlots' => $availableSlots,
            'selectedDate' => $selectedDateStr,
        ]);
    }

    #[Route('/appointments/new', name: 'appointment_store', methods: ['POST'])]
    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.create');

        $user = Gate::getUser();
        $submittedDoctorId = (int)($_POST['doctor_id'] ?? 0);

        if ($user->hasPermission('appointment.edit.own') && !$user->hasPermission('appointment.edit.any') && $user->getId() !== $submittedDoctorId) {
            http_response_code(403);
            echo "Доступ заборонено: Ви можете створювати записи лише для себе.";
            exit();
        }

        $rawInput = $_POST;
        $waitlistId = (int)($rawInput['waitlist_id'] ?? 0);
        $errors = null;

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
        $rules = [
            'patient_id' => ['required', 'numeric'],
            'doctor_id' => ['required', 'numeric'],
            'service_id' => ['required', 'numeric'],
            'room_id' => ['numeric'],
            'start_time' => ['required', 'datetime'],
            'end_time' => ['required', 'datetime'],
        ];

        if (!$validator->validate($rawInput, $rules)) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $rawInput;
            header('Location: /appointments/new?' . http_build_query($rawInput));
            exit();
        }

        $selectedDoctorId = (int)$rawInput['doctor_id'];
        $selectedServiceId = (int)$rawInput['service_id'];
        $startTime = new \DateTime($rawInput['start_time']);

        $availableSlots = $this->schedulingService->getAvailableTimeSlots($selectedDoctorId, $startTime, $selectedServiceId);

        $isSlotAvailable = false;
        foreach ($availableSlots as $slot) {
            if ($slot['time']->format('Y-m-d H:i:s') === $startTime->format('Y-m-d H:i:s') && $slot['available']) {
                $isSlotAvailable = true;
                break;
            }
        }

        if (!$isSlotAvailable) {
            $errors['start_time'] = 'The selected time slot is no longer available. Please choose another one.';
        }

        $roomValidation = $this->schedulingService->validateAppointmentBooking([
            'doctor_id' => $selectedDoctorId,
            'start_time' => $rawInput['start_time'],
            'end_time' => $rawInput['end_time'],
            'room_id' => $rawInput['room_id'] ?? null
        ]);

        if (!$roomValidation['valid']) {
            foreach ($roomValidation['errors'] as $error) {
                $errors[$error['field']] = $error['message'];
            }
        }

        if (!empty($errors)) {
            $patients = $this->patientRepository->findAllActive();
            $doctors = $this->userRepository->findAllDoctors();
            $services = $this->serviceRepository->findAll();
            $rooms = $this->roomRepository->findAvailable();

            $patientOptions = [];
            foreach ($patients as $patient) {
                $patientOptions[$patient['id']] = $patient['full_name'];
            }
            $doctorOptions = [];
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }

            $selectedDateStr = $startTime->format('Y-m-d');
            $availableSlots = $this->schedulingService->getAvailableTimeSlots($selectedDoctorId, $startTime, $selectedServiceId);

            $roomOptions = [];
            foreach ($rooms as $room) {
                $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
            }

            $serviceOptions = [];
            foreach ($services as $service) {
                $serviceOptions[$service['id']] = $service['name'] . ' (' . $service['duration_minutes'] . ' хв)';
            }

            View::render('@modules/Appointment/templates/new.html.twig', [
                'errors' => $errors,
                'old' => $rawInput,
                'patients' => $patientOptions,
                'doctors' => $doctorOptions,
                'services' => $serviceOptions,
                'servicesForJs' => $services,
                'rooms' => $roomOptions,
                'availableSlots' => $availableSlots,
                'selectedDate' => $selectedDateStr,
            ]);
            return;
        }

        $dataToSave = $rawInput;
        $dataToSave['waitlist_id'] = ($waitlistId > 0) ? $waitlistId : null;

        $this->appointmentRepository->save($dataToSave);

        if ($waitlistId > 0) {
            $this->appointmentRepository->updateWaitlistStatus($waitlistId, 'booked');
        }

        $patient = $this->patientRepository->findById((int)$rawInput['patient_id']);
        $doctor = $this->userRepository->findById($selectedDoctorId);
        if ($patient && $doctor) {
            $message = sprintf(
                'New appointment: Patient %s with Dr. %s at %s.',
                $patient['first_name'] . ' ' . $patient['last_name'],
                $doctor['first_name'] . ' ' . $doctor['last_name'],
                $startTime->format('Y-m-d H:i')
            );
            $this->notificationService->createNotification($doctor['id'], $message);
        }

        header('Location: /appointments');
        exit();
    }

    private function normalizeDateTime(string $value): \DateTime
    {
        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
            'd.m.Y, H:i',
            'd.m.Y H:i',
            'd.m.Y',
        ];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt instanceof \DateTime) {
                return $dt;
            }
        }
        return new \DateTime($value);
    }

    #[Route('/appointments/json', name: 'appointment_json', methods: ['GET'])]
    public function json(): void
    {
        AuthGuard::check();
        $user = Gate::getUser();
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $appointments = [];

        if (Gate::allows('appointment.view.any')) {
            if ($start && $end) {
                $appointments = $this->appointmentRepository->findByDateRange($start, $end);
            } else {
                $appointments = $this->appointmentRepository->findAll();
            }
        } elseif (Gate::allows('appointment.view.own')) {
            if ($user && $user->getId()) {
                if ($start && $end) {
                    $appointments = $this->appointmentRepository->findByDoctorIdAndDateRange($user->getId(), $start, $end);
                } else {
                    $appointments = $this->appointmentRepository->findByDoctorId($user->getId());
                }
            }
        }

        $events = [];

        $statusColors = [
            'scheduled' => '#2185d0',
            'completed' => '#21ba45',
            'cancelled' => '#db2828',
            'no-show' => '#fbbd08',
        ];

        foreach ($appointments as $appointment) {
            $events[] = [
                'title' => $appointment['patient_name'] . ' (' . $appointment['doctor_name'] . ')',
                'start' => $appointment['start_time'],
                'end' => $appointment['end_time'],
                'id' => $appointment['id'],
                'color' => $statusColors[$appointment['status']] ?? '#767676',
                'resourceId' => $appointment['doctor_id'],
            ];

            if (!empty($appointment['room_id'])) {
                $events[] = [
                    'title' => $appointment['patient_name'] . ' (' . ($appointment['room_name'] ?? 'Кімната ' . $appointment['room_id']) . ')',
                    'start' => $appointment['start_time'],
                    'end' => $appointment['end_time'],
                    'id' => 'room_' . $appointment['id'],
                    'color' => $statusColors[$appointment['status']] ?? '#767676',
                    'resourceId' => 'room_' . $appointment['room_id'],
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($events);
    }

    #[Route('/appointments/show', name: 'appointment_show', methods: ['GET'])]
    public function show(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('appointment.view', ['id' => $id]);

        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        View::render('@modules/Appointment/templates/show.html.twig', ['appointment' => $appointment]);
    }

    #[Route('/appointments/edit', name: 'appointment_edit', methods: ['GET'])]
    public function edit(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('appointment.edit', ['id' => $id]);

        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        $user = Gate::getUser();
        $patients = $this->patientRepository->findAllActive();
        $doctors = $this->userRepository->findAllDoctors();
        $rooms = $this->roomRepository->findAll();

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $doctorOptions = [];
        if ($user->hasPermission('appointment.edit.own') && !$user->hasPermission('appointment.edit.any')) {
            foreach ($doctors as $doctor) {
                if ((int)$doctor['id'] === $user->getId()) {
                    $doctorOptions[$doctor['id']] = $doctor['full_name'];
                    break;
                }
            }
        } else {
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }
        }

        $roomOptions = [];
        foreach ($rooms as $room) {
            $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
        }

        View::render('@modules/Appointment/templates/edit.html.twig', [
            'appointment' => $appointment,
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
            'rooms' => $roomOptions,
        ]);
    }

    #[Route('/appointments/edit', name: 'appointment_update', methods: ['POST'])]
    public function update(): void
    {
        AuthGuard::check();
        $id = (int)($_POST['id'] ?? 0);
        Gate::authorize('appointment.edit', ['id' => $id]);

        $rawInput = $_POST;

        foreach (['start_time', 'end_time'] as $field) {
            if (!empty($_POST[$field])) {
                try {
                    $dt = $this->normalizeDateTime($_POST[$field]);
                    $_POST[$field] = $dt->format('Y-m-d H:i:s');
                    $_POST[$field . '_input'] = $dt->format('Y-m-d\TH:i');
                } catch (\Exception $e) {
                    // keep raw
                }
            }
        }

        $id = (int)($_POST['id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        $errors = null;
        $validator = new \App\Core\Validation\Validator(Database::getInstance());
        $rules = [
            'patient_id' => ['required', 'numeric'],
            'doctor_id' => ['required', 'numeric'],
            'room_id' => ['numeric'],
            'start_time' => ['required', 'datetime'],
            'end_time' => ['required', 'datetime'],
            'status' => ['required', 'in:scheduled,completed,cancelled,no-show'],
        ];

        if (!empty($_POST['start_time']) && !empty($_POST['end_time'])) {
            if (strtotime($_POST['end_time']) <= strtotime($_POST['start_time'])) {
                $validator->addError('end_time', 'Час закінчення має бути пізніше за час початку.');
            }
        }

        if (!$validator->validate($_POST, $rules)) {
            $errors = [];
            foreach ($validator->getErrors() as $key => $messages) {
                $errors[$key] = is_array($messages) ? reset($messages) : $messages;
            }
        }

        if (!empty($_POST['start_time']) && !empty($_POST['end_time'])) {
            $roomValidation = $this->schedulingService->validateAppointmentBooking([
                'doctor_id' => (int)$_POST['doctor_id'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'room_id' => $_POST['room_id'] ?? null,
                'exclude_id' => $id
            ]);

            if (!$roomValidation['valid']) {
                foreach ($roomValidation['errors'] as $error) {
                    $errors[$error['field']] = $error['message'];
                }
            }
        }

        if (!empty($errors)) {
            $patients = $this->patientRepository->findAllActive();
            $doctors = $this->userRepository->findAllDoctors();
            $rooms = $this->roomRepository->findAll();
            $patientOptions = [];
            foreach ($patients as $patient) {
                $patientOptions[$patient['id']] = $patient['full_name'];
            }
            $doctorOptions = [];
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }
            $roomOptions = [];
            foreach ($rooms as $room) {
                $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
            }

            $patients = $this->patientRepository->findAllActive();
            $doctors = $this->userRepository->findAllDoctors();
            $patientOptions = [];
            foreach ($patients as $patient) {
                $patientOptions[$patient['id']] = $patient['full_name'];
            }
            $doctorOptions = [];
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }

            $roomOptions = [];
            foreach ($rooms as $room) {
                $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
            }

            View::render('@modules/Appointment/templates/edit.html.twig', [
                'errors' => $errors,
                'appointment' => $appointment,
                'old' => array_merge($rawInput, [
                    'start_time' => $_POST['start_time_input'] ?? $rawInput['start_time'] ?? null,
                    'end_time' => $_POST['end_time_input'] ?? $rawInput['end_time'] ?? null,
                ]),
                'patients' => $patientOptions,
                'doctors' => $doctorOptions,
                'rooms' => $roomOptions,
            ]);
            return;
        }

        $this->appointmentRepository->update($id, $_POST);
        header('Location: /appointments/show?id=' . $id);
        exit();
    }

    #[Route('/appointments/cancel', name: 'appointment_cancel', methods: ['POST'])]
    public function cancel(): void
    {
        AuthGuard::check();
        $id = (int)($_POST['id'] ?? 0);
        Gate::authorize('appointment.cancel', ['id' => $id]);

        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        $this->appointmentRepository->updateStatus($id, 'cancelled');

        $patient = $this->patientRepository->findById($appointment['patient_id']);
        $doctor = $this->userRepository->findById($appointment['doctor_id']);

        if ($patient) {
            $messagePatient = sprintf(
                'Ваш запис до лікаря %s на %s скасовано.',
                $doctor['first_name'] . ' ' . $doctor['last_name'],
                $appointment['start_time']
            );
        }
        if ($doctor) {
            $messageDoctor = sprintf(
                'Запис пацієнта %s на %s скасовано.',
                $patient['first_name'] . ' ' . $patient['last_name'],
                $appointment['start_time']
            );
            $this->notificationService->createNotification($doctor['id'], $messageDoctor);
        }

        header('Location: /appointments/show?id=' . $id);
        exit();
    }

    #[Route('/appointments/waitlist', name: 'appointment_waitlist', methods: ['GET'])]
    public function showWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.view.any');

        $waitlistEntries = $this->appointmentRepository->getWaitlistEntries('pending');
        $patients = $this->patientRepository->findAllActive();
        $doctors = $this->userRepository->findAllDoctors();

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $doctorOptions = [];
        foreach ($doctors as $doctor) {
            $doctorOptions[$doctor['id']] = $doctor['full_name'];
        }

        View::render('@modules/Appointment/templates/waitlist.html.twig', [
            'waitlistEntries' => $waitlistEntries,
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
        ]);
    }

    #[Route('/appointments/waitlist/add', name: 'appointment_add_waitlist', methods: ['POST'])]
    public function addPatientToWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.create');

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
        $rules = [
            'patient_id' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $waitlistEntries = $this->appointmentRepository->getWaitlistEntries('pending');
            $patients = $this->patientRepository->findAllActive();
            $doctors = $this->userRepository->findAllDoctors();

            $patientOptions = [];
            foreach ($patients as $patient) {
                $patientOptions[$patient['id']] = $patient['full_name'];
            }

            $doctorOptions = [];
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }

            View::render('@modules/Appointment/templates/waitlist.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'waitlistEntries' => $waitlistEntries,
                'patients' => $patientOptions,
                'doctors' => $doctorOptions,
            ]);
            return;
        }

        $this->appointmentRepository->addToWaitlist($_POST);
        header('Location: /appointments/waitlist');
        exit();
    }

    public function showLoadAnalytics(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.view.any');

        $date = $_GET['date'] ?? date('Y-m-d');
        $doctorLoad = $this->appointmentRepository->getDoctorDailyLoad($date);

        View::render('@modules/Appointment/templates/load_analytics.html.twig', [
            'date' => $date,
            'doctorLoad' => $doctorLoad,
        ]);
    }

    public function getAvailableSlotsApi(): void
    {
        header('Content-Type: application/json');

        $selectedDoctorId = (int)($_GET['doctor_id'] ?? 0);
        $selectedDateStr = $_GET['date'] ?? null;
        $selectedServiceId = (int)($_GET['service_id'] ?? 0);

        if (!$selectedDoctorId || !$selectedDateStr || !$selectedServiceId) {
            echo json_encode(['error' => 'Doctor, service, and date are required.']);
            return;
        }

        try {
            $selectedDate = new \DateTime($selectedDateStr);
            $availableSlots = $this->schedulingService->getAvailableTimeSlots($selectedDoctorId, $selectedDate, $selectedServiceId);

            $formattedSlots = [];
            foreach ($availableSlots as $slot) {
                $formattedSlots[] = [
                    'value' => $slot['time']->format('Y-m-d H:i:s'),
                    'label' => $slot['time']->format('H:i'),
                    'available' => $slot['available'],
                    'is_in_past' => $slot['is_in_past'],
                    'is_booked' => $slot['is_booked']
                ];
            }

            echo json_encode($formattedSlots);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format.']);
        }
    }

    public function fulfillWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.create');
        $id = (int)($_GET['id'] ?? 0);
        $entry = $this->appointmentRepository->findWaitlistById($id);
        if (!$entry) {
            http_response_code(404);
            echo "Заявку не знайдено";
            return;
        }

        $patients = $this->patientRepository->findAllActive();
        $doctors = $this->userRepository->findAllDoctors();
        $services = $this->serviceRepository->findAll();
        $rooms = $this->roomRepository->findAll();

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $doctorOptions = [];
        foreach ($doctors as $doctor) {
            $doctorOptions[$doctor['id']] = $doctor['full_name'];
        }

        $serviceOptions = [];
        foreach ($services as $service) {
            $serviceOptions[$service['id']] = $service['name'] . ' (' . $service['duration_minutes'] . ' хв)';
        }

        $roomOptions = [];
        foreach ($rooms as $room) {
            $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
        }

        $prefill = [
            'patient_id' => $entry['patient_id'],
            'doctor_id' => $entry['desired_doctor_id'],
            'waitlist_id' => $id,
        ];

        View::render('@modules/Appointment/templates/new.html.twig', [
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
            'services' => $serviceOptions,
            'servicesForJs' => $services,
            'rooms' => $roomOptions,
            'old' => array_merge($prefill, $_GET),
            'availableSlots' => [],
            'selectedDate' => date('Y-m-d'),
        ]);
    }

    public function cancelWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointment.edit.any');
        $id = (int)($_POST['id'] ?? 0);
        $entry = $this->appointmentRepository->findWaitlistById($id);
        if (!$entry) {
            http_response_code(404);
            echo "Заявку не знайдено";
            return;
        }
        $this->appointmentRepository->updateWaitlistStatus($id, 'cancelled');
        header('Location: /appointments/waitlist');
        exit();
    }
}
