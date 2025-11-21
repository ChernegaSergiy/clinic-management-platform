<?php

namespace App\Controller;

use App\Core\Validator;
use App\Core\View;
use App\Repository\MedicalRecordRepository;
use App\Repository\PatientRepository;
use App\Core\CSVExporter;

class PatientController
{
    private PatientRepository $patientRepository;
    private MedicalRecordRepository $medicalRecordRepository;

    public function __construct()
    {
        $this->patientRepository = new PatientRepository();
        $this->medicalRecordRepository = new MedicalRecordRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $searchTerm = $_GET['search'] ?? '';
        $patients = $this->patientRepository->findAll($searchTerm);
        View::render('patients/index.html.twig', [
            'patients' => $patients,
            'searchTerm' => $searchTerm,
        ]);
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        View::render('patients/new.html.twig');
    }

    public function store(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $validator = new Validator();
        $rules = [
            'last_name' => ['required'],
            'first_name' => ['required'],
            'birth_date' => ['required'],
            'gender' => ['required'],
            'phone' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            View::render('patients/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
            ]);
            return;
        }

        if (!$this->patientRepository->save($_POST)) {
            $errors['duplicate'] = 'Пацієнт з такими ПІБ та датою народження вже існує.';
            View::render('patients/new.html.twig', [
                'errors' => $errors,
                'old' => $_POST,
            ]);
            return;
        }
        
        header('Location: /patients');
        exit();
    }

    public function show(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено"; // Поки що просто текст
            return;
        }

        $medicalRecords = $this->medicalRecordRepository->findByPatientId($id);

        View::render('patients/show.html.twig', [
            'patient' => $patient,
            'medical_records' => $medicalRecords,
        ]);
    }

    public function edit(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        View::render('patients/edit.html.twig', ['patient' => $patient]);
    }

    public function update(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        $validator = new Validator();
        $rules = [
            'last_name' => ['required'],
            'first_name' => ['required'],
            'birth_date' => ['required'],
            'gender' => ['required'],
            'phone' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            View::render('patients/edit.html.twig', [
                'errors' => $validator->getErrors(),
                'patient' => array_merge($patient, $_POST),
            ]);
            return;
        }

        $this->patientRepository->update($id, $_POST);
        header('Location: /patients/show?id=' . $id);
        exit();
    }

    public function exportCsv(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $patients = $this->patientRepository->getAllForExport();

        if (empty($patients)) {
            // Optionally handle cases with no data to export
            header('Location: /patients');
            exit();
        }

        $headers = array_keys($patients[0]);
        $exporter = new CSVExporter($headers, $patients);
        $exporter->download('patients_export.csv');
    }
}
