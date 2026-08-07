<?php

namespace App\Bundles\PatientBundle\Controller;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\InsuranceBundle\Repository\ClaimRepository;
use App\Bundles\InsuranceBundle\Repository\InsuranceCompanyRepository;
use App\Bundles\InsuranceBundle\Repository\PatientInsurancePolicyRepository;
use App\Bundles\InsuranceBundle\Service\InsuranceService;
use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepositoryInterface;
use App\Bundles\PatientBundle\Repository\PatientRepositoryInterface;
use App\Core\Export\CsvExporter;
use App\Core\Export\JsonExporter;
use App\Core\Validation\Validator;
use App\Module\Billing\Repository\InvoiceRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatientController extends \App\Core\Controller\AbstractController
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
    public function index() : Response
    {
        $this->checkAuth();
        $searchTerm = $_GET['search'] ?? '';
        $user = $this->gate->getUser();
        $patients = [];

        if ($this->gate->allows('patient.view.any')) {
            $patients = $this->patientRepository->findAll($searchTerm);
        } elseif ($this->gate->allows('patient.view.own')) {
            if ($user && $user->getId()) {
                $patientIds = $this->appointmentRepository->findPatientIdsByDoctor($user->getId());
                $patients = $this->patientRepository->findByIds($patientIds, $searchTerm);
            }
        }

        return $this->render('@Patient/index.html.twig', [
            'patients' => $patients,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/patients/new', name: 'patient_create', methods: ['GET'])]
    public function create() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.create');
        return $this->render('@Patient/new.html.twig');
    }

    #[Route('/patients/new', name: 'patient_store', methods: ['POST'])]
    public function store() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.create');

        $validator = $this->validator;
        $rules = [
            'last_name' => ['required'],
            'first_name' => ['required'],
            'birth_date' => ['required'],
            'gender' => ['required'],
            'phone' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            return $this->render('@Patient/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
            ]);
        }

        if (!$this->patientRepository->save($_POST)) {
            $errorCode = $this->patientRepository->getLastError();
            $errors = [];
            if ('tax_id_exists' === $errorCode) {
                $errors['tax_id'] = 'РНОКПП вже використовується іншим пацієнтом.';
            } elseif ('patient_exists' === $errorCode) {
                $errors['duplicate'] = 'Пацієнт з такими ПІБ та датою народження вже існує.';
            } else {
                $errors['save'] = 'Не вдалося зберегти пацієнта. Спробуйте ще раз.';
            }
            return $this->render('@Patient/new.html.twig', [
                'errors' => $errors,
                'old' => $_POST,
            ]);
        }

        return new RedirectResponse('/patients');
    }

    #[Route('/patients/show', name: 'patient_show', methods: ['GET'])]
    public function show() : Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->gate->authorize('patient.view', ['id' => $id]);

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
        }

        $medicalRecords = $this->medicalRecordRepository->findByPatientId($id);
        $patientPolicies = $this->patientInsurancePolicyRepository->findByPatientId($id);

        return $this->render('@Patient/show.html.twig', [
            'patient' => $patient,
            'medical_records' => $medicalRecords,
            'patient_policies' => $patientPolicies,
        ]);
    }

    #[Route('/patients/edit', name: 'patient_edit', methods: ['GET'])]
    public function edit() : Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->gate->authorize('patient.edit', ['id' => $id]);

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
        }

        return $this->render('@Patient/edit.html.twig', ['patient' => $patient]);
    }

    #[Route('/patients/edit', name: 'patient_update', methods: ['POST'])]
    public function update() : Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->gate->authorize('patient.edit', ['id' => $id]);

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
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
            return $this->render('@Patient/edit.html.twig', [
                'errors' => $validator->getErrors(),
                'patient' => array_merge($patient, $_POST),
            ]);
        }

        if (!$this->patientRepository->update($id, $_POST)) {
            $errorCode = $this->patientRepository->getLastError();
            $errors = [];
            if ('tax_id_exists' === $errorCode) {
                $errors['tax_id'] = 'РНОКПП вже використовується іншим пацієнтом.';
            } else {
                $errors['update'] = 'Не вдалося оновити дані пацієнта. Спробуйте ще раз.';
            }

            return $this->render('@Patient/edit.html.twig', [
                'errors' => $errors,
                'patient' => array_merge($patient, $_POST),
            ]);
        }
        return new RedirectResponse('/patients/show?id=' . $id);
    }

    #[Route('/patients/export-csv', name: 'patient_export_csv', methods: ['GET'])]
    public function exportCsv() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.view.any');

        $patients = $this->patientRepository->findAll();

        if (empty($patients)) {
            return new RedirectResponse('/patients');
        }

        $headers = array_keys($patients[0]);
        $exporter = new CsvExporter($headers, $patients);
        $exporter->download('patients_export.csv');

        return new Response();
    }

    #[Route('/patients/export-json', name: 'patient_export_json', methods: ['GET'])]
    public function exportPatientsToJson() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.view.any');

        $patients = $this->patientRepository->findAll();

        if (empty($patients)) {
            $_SESSION['errors']['export'] = 'Немає пацієнтів для експорту.';
            return new RedirectResponse('/patients');
        }

        $jsonExporter = new JsonExporter();
        $jsonExporter->export($patients, 'patients_export.json');

        return new Response();
    }

    #[Route('/patients/import-json', name: 'patient_import_json', methods: ['GET', 'POST'])]
    public function importPatientsFromJson() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.create');
        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            $response = $this->render('@Patient/import_json.html.twig', [
                'errors' => $_SESSION['errors'] ?? [],
                'success_message' => $_SESSION['success_message'] ?? null,
            ]);
            unset($_SESSION['errors'], $_SESSION['success_message']);
            return $response;
        }

        // Handle POST request (process uploaded file)
        if (empty($_FILES['json_file'])) {
            $_SESSION['errors']['file'] = 'Будь ласка, виберіть JSON файл для завантаження.';
            return new RedirectResponse('/patients/import-json');
        }

        $file = $_FILES['json_file'];

        if (UPLOAD_ERR_OK !== $file['error']) {
            $_SESSION['errors']['file'] = 'Помилка завантаження файлу: ' . $file['error'];
            return new RedirectResponse('/patients/import-json');
        }

        $jsonContent = file_get_contents($file['tmp_name']);
        $patientsData = json_decode($jsonContent, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $_SESSION['errors']['file'] = 'Помилка парсингу JSON файлу: ' . json_last_error_msg();
            return new RedirectResponse('/patients/import-json');
        }

        if (!is_array($patientsData) || empty($patientsData)) {
            $_SESSION['errors']['file'] = 'JSON файл не містить коректних даних пацієнтів.';
            return new RedirectResponse('/patients/import-json');
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
        return new RedirectResponse('/patients/import-json');
    }

    #[Route('/patients/toggle-status', name: 'patient_toggle_status', methods: ['POST'])]
    public function toggleStatus() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.edit.any');

        $id = (int)($_POST['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if ($patient) {
            $newStatus = 'active' === $patient['status'] ? 'archived' : 'active';
            $this->patientRepository->updateStatus($id, $newStatus);
        }

        return new RedirectResponse('/patients/show?id=' . $id);
    }

    #[Route('/patients/{patientId}/policies/add', name: 'patient_policy_add', methods: ['GET'])]
    public function addPolicy(int $patientId) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.edit', ['id' => $patientId]);

        $patient = $this->patientRepository->findById($patientId);
        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
        }

        $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();

        return $this->render('@Patient/add_policy.html.twig', [
            'patient' => $patient,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    #[Route('/patients/{patientId}/policies/store', name: 'patient_policy_store', methods: ['POST'])]
    public function storePolicy(int $patientId) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.edit', ['id' => $patientId]);

        $patient = $this->patientRepository->findById($patientId);
        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
        }

        $validator = $this->validator;
        $rules = [
            'insurance_company_id' => ['required'],
            'policy_number' => ['required'],
            'valid_from' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();
            return $this->render('@Patient/add_policy.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'insurance_companies' => $insuranceCompanies,
            ]);
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

        return new RedirectResponse('/patients/show?id=' . $patientId);
    }

    #[Route('/patients/{patientId}/policies/edit', name: 'patient_policy_edit', methods: ['GET'])]
    public function editPolicy(int $patientId) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.edit', ['id' => $patientId]);

        $policyId = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($patientId);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if (!$patient || !$policy || $policy['patient_id'] != $patientId) {
            return new Response("Ресурс не знайдено", 404);
        }

        $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();

        return $this->render('@Patient/edit_policy.html.twig', [
            'patient' => $patient,
            'policy' => $policy,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    #[Route('/patients/{patientId}/policies/update', name: 'patient_policy_update', methods: ['POST'])]
    public function updatePolicy(int $patientId) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.edit', ['id' => $patientId]);

        $policyId = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($patientId);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if (!$patient || !$policy || $policy['patient_id'] != $patientId) {
            return new Response("Ресурс не знайдено", 404);
        }

        $validator = $this->validator;
        $rules = [
            'insurance_company_id' => ['required'],
            'policy_number' => ['required'],
            'valid_from' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();
            return $this->render('@Patient/edit_policy.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'policy' => array_merge($policy, $_POST),
                'insurance_companies' => $insuranceCompanies,
            ]);
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

        return new RedirectResponse('/patients/show?id=' . $patientId);
    }

    #[Route('/patients/{patientId}/policies/delete', name: 'patient_policy_delete', methods: ['POST'])]
    public function deletePolicy(int $patientId) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('patient.edit', ['id' => $patientId]);

        $policyId = (int)($_POST['id'] ?? 0);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if ($policy && $policy['patient_id'] == $patientId) {
            $this->insuranceService->deletePatientPolicy($policyId);
        }

        return new RedirectResponse('/patients/show?id=' . $patientId);
    }
}
