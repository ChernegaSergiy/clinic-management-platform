<?php

namespace App\Controller;

use App\Core\Validator;
use App\Core\View;
use App\Repository\AppointmentRepository;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;

class AppointmentController
{
    private AppointmentRepository $appointmentRepository;
    private PatientRepository $patientRepository;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->appointmentRepository = new AppointmentRepository();
        $this->patientRepository = new PatientRepository();
        $this->userRepository = new UserRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $appointments = $this->appointmentRepository->findAll();
        $doctors = $this->userRepository->findAllDoctors();

        $doctorOptions = [];
        foreach ($doctors as $doctor) {
            $doctorOptions[] = ['id' => $doctor['id'], 'title' => $doctor['full_name']];
        }

        View::render('appointments/index.html.twig', [
            'appointments' => $appointments,
            'doctors' => $doctorOptions,
        ]);
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
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

        View::render('appointments/new.html.twig', [
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
        ]);
    }

    public function store(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $validator = new Validator();
        $rules = [
            'patient_id' => ['required'],
            'doctor_id' => ['required'],
            'start_time' => ['required'],
            'end_time' => ['required'],
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

            View::render('appointments/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patients' => $patientOptions,
                'doctors' => $doctorOptions,
            ]);
            return;
        }

        $this->appointmentRepository->save($_POST);
        header('Location: /appointments');
        exit();
    }

    public function json(): void
    {
        $appointments = $this->appointmentRepository->findAll();
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
                'resourceId' => $appointment['doctor_id'],
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($events);
    }

    public function show(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        View::render('appointments/show.html.twig', ['appointment' => $appointment]);
    }

    public function edit(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

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

        View::render('appointments/edit.html.twig', [
            'appointment' => $appointment,
            'patients' => $patientOptions,
            'doctors' => $doctorOptions,
        ]);
    }

    public function update(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($id);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        // TODO: Add validation
        $this->appointmentRepository->update($id, $_POST);
        header('Location: /appointments/show?id=' . $id);
        exit();
    }
}
