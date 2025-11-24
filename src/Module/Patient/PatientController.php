<?php

namespace App\Module\Patient;

use App\Core\Validator;
use App\Core\View;
use App\Module\MedicalRecord\Repository\MedicalRecordRepository;
use App\Module\Patient\Repository\PatientRepository;
use App\Core\CsvExporter;
use App\Core\JsonExporter;
use App\Core\AuthGuard;

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
        AuthGuard::check();
        $searchTerm = $_GET['search'] ?? '';
        $patients = $this->patientRepository->findAll($searchTerm);
        View::render('@modules/Patient/templates/index.html.twig', [
            'patients' => $patients,
            'searchTerm' => $searchTerm,
        ]);
    }

    public function create(): void
    {
        AuthGuard::check();
        View::render('@modules/Patient/templates/new.html.twig');
    }

    public function store(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'last_name' => ['required'],
            'first_name' => ['required'],
            'birth_date' => ['required'],
            'gender' => ['required'],
            'phone' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            View::render('@modules/Patient/templates/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
            ]);
            return;
        }

        if (!$this->patientRepository->save($_POST)) {
            $errorCode = $this->patientRepository->getLastError();
            $errors = [];
            if ($errorCode === 'tax_id_exists') {
                $errors['tax_id'] = 'РНОКПП вже використовується іншим пацієнтом.';
            } elseif ($errorCode === 'patient_exists') {
                $errors['duplicate'] = 'Пацієнт з такими ПІБ та датою народження вже існує.';
            } else {
                $errors['save'] = 'Не вдалося зберегти пацієнта. Спробуйте ще раз.';
            }
            View::render('@modules/Patient/templates/new.html.twig', [
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
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено"; // Поки що просто текст
            return;
        }

        $medicalRecords = $this->medicalRecordRepository->findByPatientId($id);

        View::render('@modules/Patient/templates/show.html.twig', [
            'patient' => $patient,
            'medical_records' => $medicalRecords,
        ]);
    }

    public function edit(): void
    {
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        View::render('@modules/Patient/templates/edit.html.twig', ['patient' => $patient]);
    }

    public function update(): void
    {
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'last_name' => ['required'],
            'first_name' => ['required'],
            'birth_date' => ['required'],
            'gender' => ['required'],
            'phone' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            View::render('@modules/Patient/templates/edit.html.twig', [
                'errors' => $validator->getErrors(),
                'patient' => array_merge($patient, $_POST),
            ]);
            return;
        }

        if (!$this->patientRepository->update($id, $_POST)) {
            $errorCode = $this->patientRepository->getLastError();
            $errors = [];
            if ($errorCode === 'tax_id_exists') {
                $errors['tax_id'] = 'РНОКПП вже використовується іншим пацієнтом.';
            } else {
                $errors['update'] = 'Не вдалося оновити дані пацієнта. Спробуйте ще раз.';
            }

            View::render('@modules/Patient/templates/edit.html.twig', [
                'errors' => $errors,
                'patient' => array_merge($patient, $_POST),
            ]);
            return;
        }
        header('Location: /patients/show?id=' . $id);
        exit();
    }

    public function exportCsv(): void
    {
        AuthGuard::check();

        $patients = $this->patientRepository->findAll();

        if (empty($patients)) {
            // Optionally handle cases with no data to export
            header('Location: /patients');
            exit();
        }

        $headers = array_keys($patients[0]);
        $exporter = new CsvExporter($headers, $patients);
        $exporter->download('patients_export.csv');
    }

    public function exportPatientsToJson(): void
    {
        AuthGuard::check();

        $patients = $this->patientRepository->findAll();

        if (empty($patients)) {
            $_SESSION['errors']['export'] = 'Немає пацієнтів для експорту.';
            header('Location: /patients');
            exit();
        }

        $jsonExporter = new JsonExporter();
        $jsonExporter->export($patients, 'patients_export.json');
    }

    public function importPatientsFromJson(): void
    {
        AuthGuard::check();

        // Handle GET request (display import form)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            View::render('@modules/Patient/templates/import_json.html.twig', [
                'errors' => $_SESSION['errors'] ?? [],
                'success_message' => $_SESSION['success_message'] ?? null,
            ]);
            unset($_SESSION['errors'], $_SESSION['success_message']);
            return;
        }

        // Handle POST request (process uploaded file)
        if (empty($_FILES['json_file'])) {
            $_SESSION['errors']['file'] = 'Будь ласка, виберіть JSON файл для завантаження.';
            header('Location: /patients/import-json');
            exit();
        }

        $file = $_FILES['json_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors']['file'] = 'Помилка завантаження файлу: ' . $file['error'];
            header('Location: /patients/import-json');
            exit();
        }

        $jsonContent = file_get_contents($file['tmp_name']);
        $patientsData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['errors']['file'] = 'Помилка парсингу JSON файлу: ' . json_last_error_msg();
            header('Location: /patients/import-json');
            exit();
        }

        if (!is_array($patientsData) || empty($patientsData)) {
            $_SESSION['errors']['file'] = 'JSON файл не містить коректних даних пацієнтів.';
            header('Location: /patients/import-json');
            exit();
        }

        $importedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($patientsData as $patientData) {
            $validator = new \App\Core\Validator(\App\Database::getInstance());
            $rules = [
                'last_name' => ['required'],
                'first_name' => ['required'],
                'birth_date' => ['required'],
                'gender' => ['required'],
                'phone' => ['required'],
            ];

            if (!$validator->validate($patientData, $rules)) {
                $failedCount++;
                $errorMessages = [];
                foreach ($validator->getErrors() as $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errorMessages[] = $error;
                    }
                }
                $errors[] = 'Некоректні дані для пацієнта ' . ($patientData['first_name'] ?? '') . ' ' . ($patientData['last_name'] ?? '') . ': ' . implode(', ', $errorMessages);
                continue;
            }

            if ($this->patientRepository->save($patientData)) {
                $importedCount++;
            } else {
                $failedCount++;
                $errors[] = 'Не вдалося зберегти пацієнта ' . $patientData['first_name'] . ' ' . $patientData['last_name'] . ' (можливо, дублікат).';
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors']['import'] = $errors;
        }

        $_SESSION['success_message'] = "Імпортовано {$importedCount} пацієнтів. Не вдалося імпортувати: {$failedCount}.";
        header('Location: /patients/import-json');
        exit();
    }

    public function toggleStatus(): void
    {
        AuthGuard::check();

        $id = (int)($_POST['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if ($patient) {
            $newStatus = $patient['status'] === 'active' ? 'archived' : 'active';
            $this->patientRepository->updateStatus($id, $newStatus);
        }

        header('Location: /patients/show?id=' . $id);
        exit();
    }
}
