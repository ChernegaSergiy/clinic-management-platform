<?php

namespace App\Module\Appointment;

use App\Core\Validator;
use App\Core\View;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Patient\Repository\PatientRepository;
use App\Module\User\Repository\UserRepository;
use App\Core\NotificationService;
use App\Core\AuthGuard;

class AppointmentController
{
    private AppointmentRepository $appointmentRepository;
    private PatientRepository $patientRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->appointmentRepository = new AppointmentRepository();
        $this->patientRepository = new PatientRepository();
        $this->userRepository = new UserRepository();
        $this->notificationService = new NotificationService();
    }

    public function index(): void
    {
        AuthGuard::check();
        $doctors = $this->userRepository->findAllDoctors();
        $waitlist = $this->appointmentRepository->getWaitlistEntries();
        $appointments = $this->appointmentRepository->findAll();

        $doctorOptions = [];
        foreach ($doctors as $doctor) {
            $doctorOptions[] = ['id' => $doctor['id'], 'title' => $doctor['full_name']];
        }

        View::render('@modules/Appointment/templates/index.html.twig', [
            'doctors' => $doctorOptions,
            'waitlist' => $waitlist,
            'appointments' => $appointments,
        ]);
    }

    public function publicForm(): void
    {
        $doctors = $this->userRepository->findAllDoctors();
        View::render('@modules/Appointment/templates/public/book.html.twig', [
            'doctors' => $doctors,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['public_success_message'] ?? null,
        ]);
        unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['public_success_message']);
    }

    public function submitPublicForm(): void
    {
        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required'],
            'phone' => ['required'],
            'desired_date' => ['required', 'date'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /book-appointment');
            exit();
        }

        // For now, just enqueue to waitlist with minimal fields
        $this->appointmentRepository->addToWaitlist([
            'patient_id' => null,
            'desired_doctor_id' => $_POST['doctor_id'] ?: null,
            'desired_start_time' => $_POST['desired_date'],
            'desired_end_time' => null,
            'contact_phone' => $_POST['phone'] ?? null,
            'contact_email' => $_POST['email'] ?? null,
            'notes' => $_POST['notes'] ?? null,
        ]);

        $_SESSION['public_success_message'] = 'Заявку на прийом надіслано. Ми зв\'яжемося для підтвердження.';
        header('Location: /book-appointment');
        exit();
    }

    public function rejectWaitlist(): void
    {
        AuthGuard::check();
        $id = (int)($_POST['id'] ?? 0);
        $entry = $this->appointmentRepository->findWaitlistById($id);
        if (!$entry) {
            http_response_code(404);
            echo "Заявку не знайдено";
            return;
        }
        $this->appointmentRepository->updateWaitlistStatus($id, 'rejected');
        header('Location: /appointments');
        exit();
    }

    public function create(): void
    {
        AuthGuard::check();

        $patients = $this->patientRepository->findAllActive();
        $doctors = $this->userRepository->findAllDoctors();
        $waitlistId = (int)($_GET['waitlist_id'] ?? 0);
        $prefill = [];
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
        foreach ($doctors as $doctor) {
            $doctorOptions[$doctor['id']] = $doctor['full_name'];
        }

        View::render('@modules/Appointment/templates/new.html.twig', [
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
            'old' => $prefill,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();

        $rawInput = $_POST;

        // Normalize HTML datetime-local (or localized input) to DB format before validation
        foreach (['start_time', 'end_time'] as $field) {
            if (!empty($_POST[$field])) {
                try {
                    $dt = $this->normalizeDateTime($_POST[$field]);
                    $_POST[$field] = $dt->format('Y-m-d H:i:s');
                    // Save back a value suitable for datetime-local input
                    $_POST[$field . '_input'] = $dt->format('Y-m-d\TH:i');
                } catch (\Exception $e) {
                    // leave as is; validator will catch format issues
                }
            }
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'patient_id' => ['required', 'numeric'],
            'doctor_id' => ['required', 'numeric'],
            'start_time' => ['required', 'datetime'],
            'end_time' => ['required', 'datetime'],
        ];

        // Validate end after start
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

            // Повторне завантаження даних для форми у випадку помилки
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

            View::render('@modules/Appointment/templates/new.html.twig', [
                'errors' => $errors,
                'old' => array_merge($rawInput, [
                    'start_time' => $_POST['start_time_input'] ?? $rawInput['start_time'] ?? null,
                    'end_time' => $_POST['end_time_input'] ?? $rawInput['end_time'] ?? null,
                ]),
                'patients' => $patientOptions,
                'doctors' => $doctorOptions,
            ]);
            return;
        }

        $this->appointmentRepository->save($_POST);

        $patient = $this->patientRepository->findById($_POST['patient_id']);
        $doctor = $this->userRepository->findById($_POST['doctor_id']);
        if ($patient && $doctor) {
            $message = sprintf(
                'Новий запис: Пацієнт %s до лікаря %s на %s.',
                $patient['first_name'] . ' ' . $patient['last_name'],
                $doctor['first_name'] . ' ' . $doctor['last_name'],
                $_POST['start_time']
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
        // Default to fetching all if no date range is provided (e.g., for initial load of a simple list)
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;

        if ($start && $end) {
            $appointments = $this->appointmentRepository->findByDateRange($start, $end);
        } else {
            $appointments = $this->appointmentRepository->findAll();
        }

        $events = [];

        $statusColors = [
            'scheduled' => '#2185d0', // Semantic UI Blue
            'completed' => '#21ba45', // Semantic UI Green
            'cancelled' => '#db2828', // Semantic UI Red
            'no-show' => '#fbbd08',   // Semantic UI Yellow
        ];

        foreach ($appointments as $appointment) {
            $events[] = [
                'title' => $appointment['patient_name'] . ' (' . $appointment['doctor_name'] . ')',
                'start' => $appointment['start_time'],
                'end' => $appointment['end_time'],
                'id' => $appointment['id'],
                'color' => $statusColors[$appointment['status']] ?? '#767676', // Default grey
                'resourceId' => $appointment['doctor_id'], // This is important for resource view
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($events);
    }

    public function show(): void
    {
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
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
        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
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

        View::render('@modules/Appointment/templates/edit.html.twig', [
            'appointment' => $appointment,
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
        ]);
    }

    public function waitlist(): void
    {
        AuthGuard::check();
        $entries = $this->appointmentRepository->getWaitlistEntries();
        View::render('@modules/Appointment/templates/waitlist.html.twig', [
            'entries' => $entries,
        ]);
    }

    public function update(): void
    {
        AuthGuard::check();

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

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'patient_id' => ['required', 'numeric'],
            'doctor_id' => ['required', 'numeric'],
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

            View::render('@modules/Appointment/templates/edit.html.twig', [
                'errors' => $errors,
                'appointment' => $appointment,
                'old' => array_merge($rawInput, [
                    'start_time' => $_POST['start_time_input'] ?? $rawInput['start_time'] ?? null,
                    'end_time' => $_POST['end_time_input'] ?? $rawInput['end_time'] ?? null,
                ]),
                'patients' => $patientOptions,
                'doctors' => $doctorOptions,
            ]);
            return;
        }

        $this->appointmentRepository->update($id, $_POST);
        header('Location: /appointments/show?id=' . $id);
        exit();
    }

    public function cancel(): void
    {
        AuthGuard::check();

        $id = (int)($_POST['id'] ?? 0);
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

        $date = $_GET['date'] ?? date('Y-m-d');
        $doctorLoad = $this->appointmentRepository->getDoctorDailyLoad($date);

        View::render('@modules/Appointment/templates/load_analytics.html.twig', [
            'date' => $date,
            'doctorLoad' => $doctorLoad,
        ]);
    }
}
