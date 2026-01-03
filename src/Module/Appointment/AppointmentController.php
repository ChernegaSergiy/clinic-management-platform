<?php

namespace App\Module\Appointment;

use App\Core\Validator;
use App\Core\View;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Patient\Repository\PatientRepository;
use App\Module\User\Repository\UserRepository;
use App\Core\NotificationService;
use App\Core\AuthGuard;
use App\Core\Gate;
use App\Module\Billing\Repository\ServiceRepository;
use App\Module\Schedule\Repository\DoctorScheduleRepository;
use App\Module\Schedule\Repository\ScheduleExceptionRepository;
use App\Module\Schedule\Service\SchedulingService;

class AppointmentController
{
    private AppointmentRepository $appointmentRepository;
    private PatientRepository $patientRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private SchedulingService $schedulingService;
    private ServiceRepository $serviceRepository;
    private \App\Module\Room\Repository\RoomRepository $roomRepository;

    public function __construct()
    {
        $this->appointmentRepository = new AppointmentRepository();
        $this->patientRepository = new PatientRepository();
        $this->userRepository = new UserRepository();
        $this->notificationService = new NotificationService();

        // Dependencies for SchedulingService
        $this->serviceRepository = new ServiceRepository();
        $this->roomRepository = new \App\Module\Room\Repository\RoomRepository();
        $doctorScheduleRepository = new DoctorScheduleRepository();
        $scheduleExceptionRepository = new ScheduleExceptionRepository();

        $this->schedulingService = new SchedulingService(
            $doctorScheduleRepository,
            $scheduleExceptionRepository,
            $this->appointmentRepository,
            $this->serviceRepository,
            $this->roomRepository
        );
    }

    public function index(): void
    {
        AuthGuard::check();
        $doctors = $this->userRepository->findAllDoctors();
        $services = $this->serviceRepository->findAll();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $waitlist = $this->appointmentRepository->getWaitlistEntries();
        $appointments = [];

        if (Gate::allows('appointments.read_all')) {
            $appointments = $this->appointmentRepository->findAll();
        } elseif (Gate::allows('appointments.read_assigned')) {
            if ($userId) {
                $appointments = $this->appointmentRepository->findByDoctorId($userId);
            }
        }
        // If neither permission is allowed, $appointments remains an empty array.
        // Filter doctor options for assigned view if not allowed to read all


        // Prepare doctors for calendar (need objects with id and title)
        $calendarDoctors = [];
        foreach ($doctors as $doctor) {
            if (
                Gate::allows('appointments.read_all') ||
                (Gate::allows('appointments.read_assigned') && (int)$doctor['id'] === $userId)
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

    public function submitPublicForm(): void
    {
        $rawInput = $_POST;

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function rejectWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointments.write');
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

    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('appointments.write');

        // Get logged-in user info
        $loggedInUserId = (int)($_SESSION['user']['id'] ?? 0);
        $loggedInUserRole = $_SESSION['user']['role_name'] ?? '';

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
        // Filter doctors for 'doctor' role
        if ($loggedInUserRole === 'doctor') {
            foreach ($doctors as $doctor) {
                if ((int)$doctor['id'] === $loggedInUserId) {
                    $doctorOptions[$doctor['id']] = $doctor['full_name'];
                    // Pre-select the doctor if it's the only option
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

        // Create service options array for template
        $serviceOptions = [];
        foreach ($services as $service) {
            $serviceOptions[$service['id']] = $service['name'] . ' (' . $service['duration_minutes'] . ' хв)';
        }

        // Create room options
        $roomOptions = [];
        foreach ($rooms as $room) {
            $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
        }

        View::render('@modules/Appointment/templates/new.html.twig', [
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
            'services' => $serviceOptions,
            'servicesForJs' => $services, // Pass original service objects for JavaScript
            'rooms' => $roomOptions,
            'old' => array_merge($prefill, $_GET),
            'availableSlots' => $availableSlots,
            'selectedDate' => $selectedDateStr,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();

        $role = $_SESSION['user']['role_name'] ?? '';
        $loggedInUserId = (int)($_SESSION['user']['id'] ?? 0);
        $submittedDoctorId = (int)($_POST['doctor_id'] ?? 0);

        // A doctor can only create appointments for themselves.
        // Users with broader permissions (registrar, admin) are not affected.
        if ($role === 'doctor' && $loggedInUserId !== $submittedDoctorId) {
            http_response_code(403);
            echo "Доступ заборонено: Ви можете створювати записи лише для себе.";
            exit();
        }

        Gate::authorize('appointments.write');

        $rawInput = $_POST;
        $waitlistId = (int)($rawInput['waitlist_id'] ?? 0);
        $errors = null;

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'patient_id' => ['required', 'numeric'],
            'doctor_id' => ['required', 'numeric'],
            'service_id' => ['required', 'numeric'],
            'room_id' => ['numeric'],
            'start_time' => ['required', 'datetime'],
            'end_time' => ['required', 'datetime'],
        ];

        if (!$validator->validate($rawInput, $rules)) {
            // This is a basic failure, redirect back with a generic error
            // A more robust solution would re-render the form, which is complex.
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $rawInput;
            header('Location: /appointments/new?' . http_build_query($rawInput));
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
             $errors['start_time'] = 'The selected time slot is no longer available. Please choose another one.';
        }

        // Validate room conflicts using SchedulingService
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
            // Re-render the form with all the necessary data
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

            // Create room options
            $roomOptions = [];
            foreach ($rooms as $room) {
                $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
            }

            // Create service options
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
                'servicesForJs' => $services, // Pass original service objects for JavaScript
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

        // Send notifications
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
        // Try common formats: datetime-local (with T), locale with comma, plain
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
        // Fallback to PHP's parser
        return new \DateTime($value);
    }

    public function json(): void
    {
        AuthGuard::check(); // Ensure user is authenticated for API access
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $userId = $_SESSION['user']['id'] ?? 0;
        $appointments = [];

        if (Gate::allows('appointments.read_all')) {
            if ($start && $end) {
                $appointments = $this->appointmentRepository->findByDateRange($start, $end);
            } else {
                $appointments = $this->appointmentRepository->findAll();
            }
        } elseif (Gate::allows('appointments.read_assigned')) {
            if ($userId) {
                if ($start && $end) {
                    $appointments = $this->appointmentRepository->findByDoctorIdAndDateRange($userId, $start, $end); // Assuming this method exists or needs to be created
                } else {
                    $appointments = $this->appointmentRepository->findByDoctorId($userId);
                }
            }
        }
        // If neither permission is allowed, $appointments remains an empty array.
        // If only read_assigned is allowed, ensure results are filtered by doctor_id.

        $events = [];

        $statusColors = [
            'scheduled' => '#2185d0', // Semantic UI Blue
            'completed' => '#21ba45', // Semantic UI Green
            'cancelled' => '#db2828', // Semantic UI Red
            'no-show' => '#fbbd08',   // Semantic UI Yellow
        ];

        foreach ($appointments as $appointment) {
            // Event for doctor resource
            $events[] = [
                'title' => $appointment['patient_name'] . ' (' . $appointment['doctor_name'] . ')',
                'start' => $appointment['start_time'],
                'end' => $appointment['end_time'],
                'id' => $appointment['id'],
                'color' => $statusColors[$appointment['status']] ?? '#767676', // Default grey
                'resourceId' => $appointment['doctor_id'], // Event for doctor resource
            ];
            
            // Event for room resource (if room is assigned)
            if (!empty($appointment['room_id'])) {
                $events[] = [
                    'title' => $appointment['patient_name'] . ' (' . ($appointment['room_name'] ?? 'Кімната ' . $appointment['room_id']) . ')',
                    'start' => $appointment['start_time'],
                    'end' => $appointment['end_time'],
                    'id' => 'room_' . $appointment['id'], // Different ID to avoid conflicts
                    'color' => $statusColors[$appointment['status']] ?? '#767676',
                    'resourceId' => 'room_' . $appointment['room_id'], // Room resource ID
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($events);
    }

    public function show(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('appointments.read', ['appointment_id' => $id]);

        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        View::render('@modules/Appointment/templates/show.html.twig', ['appointment' => $appointment]);
    }

    public function edit(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('appointments.write', ['appointment_id' => $id]);

        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        $patients = $this->patientRepository->findAllActive();
        $doctors = $this->userRepository->findAllDoctors();
        $rooms = $this->roomRepository->findAll();

        // Get logged-in user info
        $loggedInUserId = (int)($_SESSION['user']['id'] ?? 0);
        $loggedInUserRole = $_SESSION['user']['role_name'] ?? '';

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $doctorOptions = [];
        // Filter doctors for 'doctor' role
        if ($loggedInUserRole === 'doctor') {
            foreach ($doctors as $doctor) {
                if ((int)$doctor['id'] === $loggedInUserId) {
                    $doctorOptions[$doctor['id']] = $doctor['full_name'];
                    break;
                }
            }
        } else {
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }
        }

        // Create room options
        $roomOptions = [];
        foreach ($rooms as $room) {
            $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
        }

        // Create room options
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

    public function update(): void
    {
        AuthGuard::check();
        $id = (int)($_POST['id'] ?? 0);
        Gate::authorize('appointments.write', ['appointment_id' => $id]);

        $rawInput = $_POST;

        // Normalize datetime inputs
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
        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

        // Validate room conflicts for existing appointment
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

            // Validate room conflicts for existing appointment
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

            if ($errors) { // @phpstan-ignore-line
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
        }

        $this->appointmentRepository->update($id, $_POST);
        header('Location: /appointments/show?id=' . $id);
        exit();
    }

    public function cancel(): void
    {
        AuthGuard::check();
        $id = (int)($_POST['id'] ?? 0);
        Gate::authorize('appointments.write', ['appointment_id' => $id]);

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
            $this->notificationService->createNotification($patient['id'], $messagePatient); // Assuming patient ID is user ID for notification
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

    public function showWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointments.read');

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

    public function addPatientToWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointments.write');

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'patient_id' => ['required'],
            // 'desired_start_time' => ['required', 'date'],
            // 'desired_end_time' => ['date'],
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
        Gate::authorize('appointments.read_analytics'); // Or a more specific permission like 'dashboard.view_analytics'

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
        Gate::authorize('appointments.write');
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

        // Create service options array for template
        $serviceOptions = [];
        foreach ($services as $service) {
            $serviceOptions[$service['id']] = $service['name'] . ' (' . $service['duration_minutes'] . ' хв)';
        }

        $roomOptions = [];
        foreach ($rooms as $room) {
            $roomOptions[$room['id']] = $room['name'] . ' (' . $room['type'] . ')';
        }

        // Create prefill data from waitlist entry
        $prefill = [
            'patient_id' => $entry['patient_id'],
            'doctor_id' => $entry['desired_doctor_id'],
            'waitlist_id' => $id,
        ];

        View::render('@modules/Appointment/templates/new.html.twig', [
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
            'services' => $serviceOptions,
            'servicesForJs' => $services, // Pass original service objects for JavaScript
            'rooms' => $roomOptions,
            'old' => array_merge($prefill, $_GET),
            'availableSlots' => [],
            'selectedDate' => date('Y-m-d'),
        ]);
    }

    public function cancelWaitlist(): void
    {
        AuthGuard::check();
        Gate::authorize('appointments.write');
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
