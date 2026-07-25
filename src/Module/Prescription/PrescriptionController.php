<?php

namespace App\Module\Prescription;

use App\Database\Database;
use App\Core\Auth\Gate;
use App\Core\Validation\Validator;
use App\Module\Inventory\Repository\InventoryItemRepositoryInterface;
use App\Module\MedicalRecord\Repository\MedicalRecordRepositoryInterface;
use App\Module\Patient\Repository\PatientRepositoryInterface;
use App\Module\Prescription\Repository\PrescriptionRepository;
use App\Module\User\Repository\UserRepositoryInterface;

class PrescriptionController extends \App\Core\Controller\AbstractController
{
    private PrescriptionRepository $prescriptionRepository;
    private PatientRepositoryInterface $patientRepository;
    private MedicalRecordRepositoryInterface $medicalRecordRepository;
    private UserRepositoryInterface $userRepository;
    private InventoryItemRepositoryInterface $inventoryItemRepository;
    private Validator $validator;

    public function __construct(
        PrescriptionRepository $prescriptionRepository,
        PatientRepositoryInterface $patientRepository,
        MedicalRecordRepositoryInterface $medicalRecordRepository,
        UserRepositoryInterface $userRepository,
        InventoryItemRepositoryInterface $inventoryItemRepository,
        Validator $validator
    ) {
        $this->prescriptionRepository = $prescriptionRepository;
        $this->patientRepository = $patientRepository;
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->userRepository = $userRepository;
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->validator = $validator;
    }

    public function index(): void
    {
        $this->checkAuth();
        $searchTerm = $_GET['search'] ?? '';
        $user = Gate::getUser();
        $prescriptions = [];

        if (Gate::allows('prescription.view.any')) {
            $prescriptions = $this->prescriptionRepository->findAll($searchTerm);
        } elseif (Gate::allows('prescription.view.own')) {
            if ($user && $user->getId()) {
                $prescriptions = $this->prescriptionRepository->findByDoctorId($user->getId(), $searchTerm);
            }
        }

        $this->render('@modules/Prescription/templates/index.html.twig', [
            'prescriptions' => $prescriptions,
            'searchTerm' => $searchTerm,
        ]);
    }

    public function create(): void
    {
        $this->checkAuth();
        Gate::authorize('prescription.create', ['doctor_id' => Gate::getUser()->getId()]); // Check if current user can create prescriptions

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

        $this->render('@modules/Prescription/templates/new.html.twig', [
            'patient' => $patient,
            'doctors' => $doctorOptions,
            'medicalRecords' => $medicalRecords,
            'currentDoctorId' => Gate::getUser()->getId(),
        ]);
    }

    public function store(): void
    {
        $this->checkAuth();
        Gate::authorize('prescription.create', ['doctor_id' => $_POST['doctor_id'] ?? null]);

        $validator = $this->validator;
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

            $this->render('@modules/Prescription/templates/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'doctors' => $doctorOptions,
                'medicalRecords' => $medicalRecords,
                'currentDoctorId' => Gate::getUser()->getId(),
            ]);
            return;
        }

        $prescriptionId = $this->prescriptionRepository->save($_POST);

        if ($prescriptionId && !empty($_POST['items'])) {
            foreach ($_POST['items'] as $itemData) {
                $inventoryItem = $this->inventoryItemRepository->findByName($itemData['medication_name']);

                if ($inventoryItem && isset($itemData['dosage'])) {
                    $quantityToDeduct = (int)$itemData['dosage'];

                    if ($quantityToDeduct > 0) {
                        $this->inventoryItemRepository->decreaseQuantity(
                            $inventoryItem['id'],
                            $quantityToDeduct,
                            Gate::getUser()->getId(),
                            'Виконання рецепту #' . $prescriptionId
                        );
                    }
                }
            }
        }
        header('Location: /patients/show?id=' . $_POST['patient_id']);
        exit();
    }

    public function show(): void
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('prescription.view', ['id' => $id]);

        $prescription = $this->prescriptionRepository->findById($id);

        if (!$prescription) {
            http_response_code(404);
            echo "Рецепт не знайдено";
            return;
        }

        $this->render('@modules/Prescription/templates/show.html.twig', [
            'prescription' => $prescription,
        ]);
    }
}
