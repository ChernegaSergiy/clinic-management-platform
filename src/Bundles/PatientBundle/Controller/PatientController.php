<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Bundles\PatientBundle\Controller;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\InsuranceBundle\Repository\PatientInsurancePolicyRepository;
use App\Bundles\InsuranceBundle\Service\InsuranceService;
use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepositoryInterface;
use App\Bundles\PatientBundle\Repository\PatientRepositoryInterface;
use App\Core\Export\CsvExporter;
use App\Core\Export\JsonExporter;
use App\Core\Validation\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatientController extends AbstractController
{
    private PatientRepositoryInterface $patientRepository;
    private MedicalRecordRepositoryInterface $medicalRecordRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private InsuranceService $insuranceService;
    private PatientInsurancePolicyRepository $patientInsurancePolicyRepository;
    private Validator $validator;

    public function __construct(
        PatientRepositoryInterface $patientRepository,
        MedicalRecordRepositoryInterface $medicalRecordRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        InsuranceService $insuranceService,
        PatientInsurancePolicyRepository $patientInsurancePolicyRepository,
        Validator $validator
    ) {
        $this->patientRepository = $patientRepository;
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->patientInsurancePolicyRepository = $patientInsurancePolicyRepository;
        $this->insuranceService = $insuranceService;
        $this->validator = $validator;
    }

    #[Route('/patients', name: 'patient_index', methods: ['GET'])]
    public function index() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_VIEW');
        $searchTerm = $_GET['search'] ?? '';
        $user = $this->getUser();
        $patients = [];

        if ($this->isGranted('PATIENT_VIEW_ALL')) {
            $patients = $this->patientRepository->findAll($searchTerm);
        } elseif ($user && $user->getId()) {
            $patientIds = $this->appointmentRepository->findPatientIdsByDoctor($user->getId());
            $patients = $this->patientRepository->findByIds($patientIds, $searchTerm);
        }

        return $this->render('@Patient/index.html.twig', [
            'patients' => $patients,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/patients/new', name: 'patient_create', methods: ['GET'])]
    public function create() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_CREATE');
        return $this->render('@Patient/new.html.twig');
    }

    #[Route('/patients/new', name: 'patient_store', methods: ['POST'])]
    public function store() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_CREATE');

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

        return $this->redirectToRoute('patient_index');
    }

    #[Route('/patients/show', name: 'patient_show', methods: ['GET'])]
    public function show() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_VIEW');
        $id = (int)($_GET['id'] ?? 0);

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
        $this->denyAccessUnlessGranted('PATIENT_EDIT');
        $id = (int)($_GET['id'] ?? 0);

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
        }

        return $this->render('@Patient/edit.html.twig', ['patient' => $patient]);
    }

    #[Route('/patients/edit', name: 'patient_update', methods: ['POST'])]
    public function update() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_EDIT');
        $id = (int)($_GET['id'] ?? 0);

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
        return $this->redirectToRoute('patient_show', ['id' => $id]);
    }

    #[Route('/patients/export-csv', name: 'patient_export_csv', methods: ['GET'])]
    public function exportCsv() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_VIEW_ALL');

        $patients = $this->patientRepository->findAll();

        $headers = !empty($patients) ? array_keys($patients[0]) : ['id', 'first_name', 'last_name', 'birth_date', 'gender', 'phone', 'email', 'address', 'tax_id'];
        if (empty($patients)) {
            $patients[] = array_combine($headers, ['N/A', '', '', '', '', '', '', '', '']);
        }
        $exporter = new CsvExporter($headers, $patients);
        $csvContent = $exporter->generate();

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="patients_export.csv"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    #[Route('/patients/export-json', name: 'patient_export_json', methods: ['GET'])]
    public function exportPatientsToJson() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_VIEW_ALL');

        $patients = $this->patientRepository->findAll();

        $jsonExporter = new JsonExporter();
        $jsonContent = $jsonExporter->generate($patients);

        $response = new Response($jsonContent);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="patients_export.json"');

        return $response;
    }

    #[Route('/patients/import-json', name: 'patient_import_json', methods: ['GET', 'POST'])]
    public function importPatientsFromJson() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_CREATE');
        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            $response = $this->render('@Patient/import_json.html.twig', [
                'errors' => $_SESSION['errors'] ?? [],
                'success_message' => $_SESSION['success_message'] ?? null,
            ]);
            unset($_SESSION['errors'], $_SESSION['success_message']);
            return $response;
        }

        if (empty($_FILES['json_file'])) {
            $_SESSION['errors']['file'] = 'Будь ласка, виберіть JSON файл для завантаження.';
            return $this->redirectToRoute('patient_import_json');
        }

        $file = $_FILES['json_file'];

        if (UPLOAD_ERR_OK !== $file['error']) {
            $_SESSION['errors']['file'] = 'Помилка завантаження файлу: ' . $file['error'];
            return $this->redirectToRoute('patient_import_json');
        }

        $jsonContent = file_get_contents($file['tmp_name']);
        $patientsData = json_decode($jsonContent, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $_SESSION['errors']['file'] = 'Помилка парсингу JSON файлу: ' . json_last_error_msg();
            return $this->redirectToRoute('patient_import_json');
        }

        if (!is_array($patientsData) || empty($patientsData)) {
            $_SESSION['errors']['file'] = 'JSON файл не містить коректних даних пацієнтів.';
            return $this->redirectToRoute('patient_import_json');
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
        return $this->redirectToRoute('patient_import_json');
    }

    #[Route('/patients/toggle-status', name: 'patient_toggle_status', methods: ['POST'])]
    public function toggleStatus() : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_EDIT_ALL');

        $id = (int)($_POST['id'] ?? 0);
        $patient = $this->patientRepository->findById($id);

        if ($patient) {
            $newStatus = 'active' === $patient['status'] ? 'archived' : 'active';
            $this->patientRepository->updateStatus($id, $newStatus);
        }

        return $this->redirectToRoute('patient_show', ['id' => $id]);
    }

    #[Route('/patients/{patientId}/policies/add', name: 'patient_policy_add', methods: ['GET'])]
    public function addPolicy(int $patientId) : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_EDIT');

        $patient = $this->patientRepository->findById($patientId);
        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
        }

        $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();

        return $this->render('@Patient/policies/new.html.twig', [
            'patient' => $patient,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    #[Route('/patients/{patientId}/policies/store', name: 'patient_policy_store', methods: ['POST'])]
    public function storePolicy(int $patientId) : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_EDIT');

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
            return $this->render('@Patient/policies/new.html.twig', [
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

        return $this->redirectToRoute('patient_show', ['id' => $patientId]);
    }

    #[Route('/patients/{patientId}/policies/edit', name: 'patient_policy_edit', methods: ['GET'])]
    public function editPolicy(int $patientId) : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_EDIT');

        $policyId = (int)($_GET['id'] ?? 0);
        $patient = $this->patientRepository->findById($patientId);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if (!$patient || !$policy || $policy['patient_id'] != $patientId) {
            return new Response("Ресурс не знайдено", 404);
        }

        $insuranceCompanies = $this->insuranceService->getAllInsuranceCompanies();

        return $this->render('@Patient/policies/edit.html.twig', [
            'patient' => $patient,
            'policy' => $policy,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    #[Route('/patients/{patientId}/policies/update', name: 'patient_policy_update', methods: ['POST'])]
    public function updatePolicy(int $patientId) : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_EDIT');

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
            return $this->render('@Patient/policies/edit.html.twig', [
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

        return $this->redirectToRoute('patient_show', ['id' => $patientId]);
    }

    #[Route('/patients/{patientId}/policies/delete', name: 'patient_policy_delete', methods: ['POST'])]
    public function deletePolicy(int $patientId) : Response
    {
        $this->denyAccessUnlessGranted('PATIENT_EDIT');

        $policyId = (int)($_POST['id'] ?? 0);
        $policy = $this->insuranceService->getPatientPolicy($policyId);

        if ($policy && $policy['patient_id'] == $patientId) {
            $this->insuranceService->deletePatientPolicy($policyId);
        }

        return $this->redirectToRoute('patient_show', ['id' => $patientId]);
    }
}
