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
        //$appointments = $this->appointmentRepository->findAll(); // Removed, will be fetched via JSON
        $doctors = $this->userRepository->findAllDoctors();

        $doctorOptions = [];
        foreach ($doctors as $doctor) {
            $doctorOptions[] = ['id' => $doctor['id'], 'title' => $doctor['full_name']];
        }

        View::render('@modules/Appointment/templates/index.html.twig', [
            //'appointments' => $appointments, // Removed, will be fetched via JSON
            'doctors' => $doctorOptions,
        ]);
    }

    public function publicForm(): void
    {
        $doctors = $this->userRepository->findAllDoctors();
        View::render('@modules/Appointment/templates/public/book.html.twig', [
            'doctors' => $doctors,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
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
            'patient_id' => 0,
            'desired_doctor_id' => $_POST['doctor_id'] ?: null,
            'desired_start_time' => $_POST['desired_date'],
            'desired_end_time' => null,
            'notes' => sprintf("Заявка з публічної форми. Контакт: %s, Email: %s. Коментар: %s", $_POST['phone'], $_POST['email'] ?? '', $_POST['notes'] ?? ''),
        ]);

        $_SESSION['success_message'] = 'Заявку на прийом надіслано. Ми зв\'яжемося для підтвердження.';
        header('Location: /book-appointment');
        exit();
    }

    public function create(): void
    {
        AuthGuard::check();

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
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'patient_id' => ['required', 'numeric'],
            'doctor_id' => ['required', 'numeric'],
            'start_time' => ['required', 'datetime'],
            'end_time' => ['required', 'datetime'],
        ];

        if (!$validator->validate($_POST, $rules)) {
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
                'errors' => $validator->getErrors(),
                'old' => $_POST,
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

    public function update(): void
    {
        AuthGuard::check();

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

        if (!$validator->validate($_POST, $rules)) {
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
                'errors' => $validator->getErrors(),
                'appointment' => array_merge($appointment, $_POST),
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
