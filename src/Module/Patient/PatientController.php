<?php

namespace App\Module\Patient;

use App\Database\Database;
use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Export\CsvExporter;
use App\Core\Export\JsonExporter;
use App\Core\Http\View;
use App\Core\Validation\Validator;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\Insurance\Repository\InsuranceCompanyRepository;
use App\Module\Insurance\Repository\PatientInsurancePolicyRepository;
use App\Module\Insurance\Repository\ClaimRepository;
use App\Module\Insurance\Service\InsuranceService;
use App\Module\MedicalRecord\Repository\MedicalRecordRepositoryInterface;
use App\Module\Patient\Repository\PatientRepositoryInterface;
use App\Module\Appointment\Repository\AppointmentRepositoryInterface;
use App\Module\Billing\Repository\InvoiceRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class PatientController
{
    private PatientRepositoryInterface $patientRepository;
    private MedicalRecordRepositoryInterface $medicalRecordRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private InsuranceService $insuranceService;
    private InsuranceCompanyRepository $insuranceCompanyRepository;
    private PatientInsurancePolicyRepository $patientInsurancePolicyRepository;
    private ClaimRepository $claimRepository;
    private InvoiceRepositoryInterface $invoiceRepository;
    private Validator $validator;
    
    public function __construct(
        PatientRepositoryInterface $patientRepository,
        MedicalRecordRepositoryInterface $medicalRecordRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        InsuranceService $insuranceService,
        InsuranceCompanyRepository $insuranceCompanyRepository,
        PatientInsurancePolicyRepository $patientInsurancePolicyRepository,
        ClaimRepository $claimRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        Validator $validator
    ) {
        $this->patientRepository = $patientRepository;
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->insuranceCompanyRepository = $insuranceCompanyRepository;
        $this->patientInsurancePolicyRepository = $patientInsurancePolicyRepository;
        $this->claimRepository = $claimRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->insuranceService = $insuranceService;
        $this->validator = $validator;
    }

    #[Route('/patients', name: 'patient_index', methods: ['GET'])]
    public function index(): void
    {
        AuthGuard::check();
        $searchTerm = $_GET['search'] ?? '';
        $user = Gate::getUser();
        $patients = [];

        if (Gate::allows('patient.view.any')) {
            $patients = $this->patientRepository->findAll($searchTerm);
        } elseif (Gate::allows('patient.view.own')) {
            if ($user && $user->getId()) {
                $patientIds = $this->appointmentRepository->findPatientIdsByDoctor($user->getId());
                $patients = $this->patientRepository->findByIds($patientIds, $searchTerm);
            }
        }

        View::render('@modules/Patient/templates/index.html.twig', [
            'patients' => $patients,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/patients/new', name: 'patient_create', methods: ['GET'])]
    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('patient.create');
        View::render('@modules/Patient/templates/new.html.twig');
    }

    #[Route('/patients/new', name: 'patient_store', methods: ['POST'])]
    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('patient.create');

        $validator = $this->validator;
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

    #[Route('/patients/show', name: 'patient_show', methods: ['GET'])]
    public function show(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('patient.view', ['id' => $id]);

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено"; // Поки що просто текст
            return;
        }

        $medicalRecords = $this->medicalRecordRepository->findByPatientId($id);
        $patientPolicies = $this->patientInsurancePolicyRepository->findByPatientId($id);

        View::render('@modules/Patient/templates/show.html.twig', [
            'patient' => $patient,
            'medical_records' => $medicalRecords,
            'patient_policies' => $patientPolicies,
        ]);
    }

    #[Route('/patients/edit', name: 'patient_edit', methods: ['GET'])]
    public function edit(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('patient.edit', ['id' => $id]);

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        View::render('@modules/Patient/templates/edit.html.twig', ['patient' => $patient]);
    }

    #[Route('/patients/edit', name: 'patient_update', methods: ['POST'])]
    public function update(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('patient.edit', ['id' => $id]);

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        $validator = $this->validator;
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

    #[Route('/patients/export-csv', name: 'patient_export_csv', methods: ['GET'])]
    public function exportCsv(): void
    {
        AuthGuard::check();
        Gate::authorize('patient.view.any');

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

    #[Route('/patients/export-json', name: 'patient_export_json', methods: ['GET'])]
    public function exportPatientsToJson(): void
    {
        AuthGuard::check();
        Gate::authorize('patient.view.any');

        $patients = $this->patientRepository->findAll();

        if (empty($patients)) {
            $_SESSION['errors']['export'] = 'Немає пацієнтів для експорту.';
            header('Location: /patients');
            exit();
        }

        $jsonExporter = new JsonExporter();
        $jsonExporter->export($patients, 'patients_export.json');
    }

    #[Route('/patients/import-json', name: 'patient_import_json', methods: ['GET', 'POST'])]
    public function importPatientsFromJson(): void
    {
        AuthGuard::check();
        Gate::authorize('patient.create');
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
            $validator = $this->validator;
            $rules = [
                'last_name' => ['required'],
                'first_name' => ['required'],
                'birth_date' => ['required'],
                'gender' => ['required'],
                'phone' => ['required'],
            ];

            if (
                !$validator->validate(
                    $patientData,
                    $rules
                )
            ) {
                $failedCount++;
                $errorMessages = [];
                foreach ($validator->getErrors() as $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errorMessages[] = $error;
                    }
                }
                $errorMsg = 'Некоректні дані для пацієнта ';
                $errorMsg .= ($patientData['first_name'] ?? '') . ' ' . ($patientData['last_name'] ?? '');
                $errorMsg .= ': ' . implode(', ', $errorMessages);
                $errors[] = $errorMsg;
                continue;
            }

            if ($this->patientRepository->save($patientData)) {
                $importedCount++;
            } else {
                $failedCount++;
                $errorMsg = 'Не вдалося зберегти пацієнта ';
                $errorMsg .= ($patientData['first_name'] ?? '') . ' ' . ($patientData['last_name'] ?? '');
                $errorMsg .= ' (можливо, дублікат).';
                $errors[] = $errorMsg;
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors']['import'] = $errors;
        }

        $_SESSION['success_message'] = "Імпортовано {$importedCount} пацієнтів. "
                                       . "Не вдалося імпортувати: {$failedCount}.";
        header('Location: /patients/import-json');
        exit();
    }

    #[Route('/patients/toggle-status', name: 'patient_toggle_status', methods: ['POST'])]
    public function toggleStatus(): void
    {
        AuthGuard::check();
        Gate::authorize('patient.edit.any');

        $id = (int)($_POST['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if ($patient) {
            $newStatus = $patient['status'] === 'active' ? 'archived' : 'active';
            $this->patientRepository->updateStatus($id, $newStatus);
        }

        header('Location: /patients/show?id=' . $id);
        exit();
    }

    #[Route('/patients/{patientId}/policies/add', name: 'patient_policy_add', methods: ['GET'])]
    public function addPolicy(int $patientId): void
    {
        AuthGuard::check();
        Gate::authorize('patient.edit', ['id' => $patientId]);

        $patient = $this->patientRepository->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();

        View::render('@modules/Patient/templates/add_policy.html.twig', [
            'patient' => $patient,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    #[Route('/patients/{patientId}/policies/store', name: 'patient_policy_store', methods: ['POST'])]
    public function storePolicy(int $patientId): void
    {
        AuthGuard::check();
        Gate::authorize('patient.edit', ['id' => $patientId]);

        $patient = $this->patientRepository->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            echo "Пацієнта не знайдено";
            return;
        }

        $validator = $this->validator;
        $rules = [
            'insurance_company_id' => ['required'],
            'policy_number' => ['required'],
            'valid_from' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();
            View::render('@modules/Patient/templates/add_policy.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'insurance_companies' => $insuranceCompanies,
            ]);
            return;
        }

        $isActive = isset($_POST['is_active']);

        $this->insuranceService->addPolicyToPatient(
            $patientId,
            (int)$_POST['insurance_company_id'],
            $_POST['policy_number'],
            $_POST['group_number'],
            $_POST['valid_from'],
            $_POST['valid_to'],
            $isActive
        );

        header('Location: /patients/show?id=' . $patientId);
        exit();
    }

    #[Route('/patients/{patientId}/policies/edit', name: 'patient_policy_edit', methods: ['GET'])]
    public function editPolicy(int $patientId): void
    {
        AuthGuard::check();
        Gate::authorize('patient.edit', ['id' => $patientId]);

        $policyId = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($patientId);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if (!$patient || !$policy || $policy['patient_id'] != $patientId) {
            http_response_code(404);
            echo "Ресурс не знайдено";
            return;
        }

        $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();

        View::render('@modules/Patient/templates/edit_policy.html.twig', [
            'patient' => $patient,
            'policy' => $policy,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    #[Route('/patients/{patientId}/policies/update', name: 'patient_policy_update', methods: ['POST'])]
    public function updatePolicy(int $patientId): void
    {
        AuthGuard::check();
        Gate::authorize('patient.edit', ['id' => $patientId]);

        $policyId = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($patientId);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if (!$patient || !$policy || $policy['patient_id'] != $patientId) {
            http_response_code(404);
            echo "Ресурс не знайдено";
            return;
        }

        $validator = $this->validator;
        $rules = [
            'insurance_company_id' => ['required'],
            'policy_number' => ['required'],
            'valid_from' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();
            View::render('@modules/Patient/templates/edit_policy.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'policy' => array_merge($policy, $_POST),
                'insurance_companies' => $insuranceCompanies,
            ]);
            return;
        }

        $isActive = isset($_POST['is_active']);

        $this->insuranceService->updatePatientPolicy(
            $policyId,
            $patientId,
            (int)$_POST['insurance_company_id'],
            $_POST['policy_number'],
            $_POST['group_number'],
            $_POST['valid_from'],
            $_POST['valid_to'],
            $isActive
        );

        header('Location: /patients/show?id=' . $patientId);
        exit();
    }

    #[Route('/patients/{patientId}/policies/delete', name: 'patient_policy_delete', methods: ['POST'])]
    public function deletePolicy(int $patientId): void
    {
        AuthGuard::check();
        Gate::authorize('patient.edit', ['id' => $patientId]);

        $policyId = (int)($_POST['id'] ?? 0);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if ($policy && $policy['patient_id'] == $patientId) {
            $this->insuranceService->deletePatientPolicy($policyId);
        }

        header('Location: /patients/show?id=' . $patientId);
        exit();
    }
}
