<?php

namespace App\Controller;

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
        View::render('appointments/index.html.twig', ['appointments' => $appointments]);
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

        // TODO: Add validation
        $this->appointmentRepository->save($_POST);
        header('Location: /appointments');
        exit();
    }

    public function json(): void
    {
        $appointments = $this->appointmentRepository->findAll();
        $events = [];
        foreach ($appointments as $appointment) {
            $events[] = [
                'title' => $appointment['patient_name'] . ' (' . $appointment['doctor_name'] . ')',
                'start' => $appointment['start_time'],
                'end' => $appointment['end_time'],
                'id' => $appointment['id'],
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($events);
    }
}
