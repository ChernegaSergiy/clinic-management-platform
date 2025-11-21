<?php

namespace App\Controller;

use App\Core\Validator;
use App\Core\View;
use App\Repository\PatientRepository;
use App\Repository\PrescriptionRepository;
use App\Repository\MedicalRecordRepository;
use App\Repository\UserRepository;

class PrescriptionController
{
    private PrescriptionRepository $prescriptionRepository;
    private PatientRepository $patientRepository;
    private MedicalRecordRepository $medicalRecordRepository;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->prescriptionRepository = new PrescriptionRepository();
        $this->patientRepository = new PatientRepository();
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->userRepository = new UserRepository();
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $patientId = (int)($_GET['patient_id'] ?? 0);
        $patient = $this->patientRepository->findById($patientId);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        $doctors = $this->userRepository->findAllDoctors();
        $medicalRecords = $this->medicalRecordRepository->findByPatientId($patientId);

        $doctorOptions = [];
        foreach ($doctors as $doctor) {
            $doctorOptions[$doctor['id']] = $doctor['full_name'];
        }

        View::render('prescriptions/new.html.twig', [
            'patient' => $patient,
            'doctors' => $doctorOptions,
            'medicalRecords' => $medicalRecords,
            'currentDoctorId' => $_SESSION['user']['id'],
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
            'issue_date' => ['required', 'date'],
            'items' => ['required', 'array'],
            'items.*.medication_name' => ['required'],
            'items.*.dosage' => ['required'],
            'items.*.frequency' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $patient = $this->patientRepository->findById($_POST['patient_id']);
            $doctors = $this->userRepository->findAllDoctors();
            $medicalRecords = $this->medicalRecordRepository->findByPatientId($_POST['patient_id']);

            $doctorOptions = [];
            foreach ($doctors as $doctor) {
                $doctorOptions[$doctor['id']] = $doctor['full_name'];
            }

            View::render('prescriptions/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'doctors' => $doctorOptions,
                'medicalRecords' => $medicalRecords,
                'currentDoctorId' => $_SESSION['user']['id'],
            ]);
            return;
        }

        $this->prescriptionRepository->save($_POST);
        header('Location: /patients/show?id=' . $_POST['patient_id']);
        exit();
    }

    public function show(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $prescription = $this->prescriptionRepository->findById($id);

        if (!$prescription) {
            http_response_code(404);
            echo "Рецепт не знайдено";
            return;
        }

        View::render('prescriptions/show.html.twig', [
            'prescription' => $prescription,
        ]);
    }
}
