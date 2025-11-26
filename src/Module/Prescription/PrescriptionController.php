<?php

namespace App\Module\Prescription;

use App\Core\Validator;
use App\Core\View;
use App\Module\Patient\Repository\PatientRepository;
use App\Module\Prescription\Repository\PrescriptionRepository;
use App\Module\MedicalRecord\Repository\MedicalRecordRepository;
use App\Module\User\Repository\UserRepository;
use App\Module\Inventory\Repository\InventoryItemRepository;
use App\Core\AuthGuard;

class PrescriptionController
{
    private PrescriptionRepository $prescriptionRepository;
    private PatientRepository $patientRepository;
    private MedicalRecordRepository $medicalRecordRepository;
    private UserRepository $userRepository;
    private InventoryItemRepository $inventoryItemRepository;

    public function __construct()
    {
        $this->prescriptionRepository = new PrescriptionRepository();
        $this->patientRepository = new PatientRepository();
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->userRepository = new UserRepository();
        $this->inventoryItemRepository = new InventoryItemRepository();
    }

    public function index(): void
    {
        AuthGuard::check();
        $prescriptions = $this->prescriptionRepository->findAll();
        View::render('@modules/Prescription/templates/index.html.twig', [
            'prescriptions' => $prescriptions,
        ]);
    }

    public function create(): void
    {
        AuthGuard::check();

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

        View::render('@modules/Prescription/templates/new.html.twig', [
            'patient' => $patient,
            'doctors' => $doctorOptions,
            'medicalRecords' => $medicalRecords,
            'currentDoctorId' => $_SESSION['user']['id'],
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

            View::render('@modules/Prescription/templates/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'doctors' => $doctorOptions,
                'medicalRecords' => $medicalRecords,
                'currentDoctorId' => $_SESSION['user']['id'],
            ]);
            return;
        }

        $prescriptionId = $this->prescriptionRepository->save($_POST);

        if ($prescriptionId && !empty($_POST['items'])) {
            foreach ($_POST['items'] as $itemData) {
                // Find inventory item by medication name
                // This is a simplified approach. In a real system, you'd link by ID.
                $inventoryItem = $this->inventoryItemRepository->findByName($itemData['medication_name']);

                if ($inventoryItem && isset($itemData['dosage'])) {
                    // Assuming dosage is a simple number for quantity to deduct
                    // Or, more complex logic to parse dosage to quantity
                    $quantityToDeduct = (int)$itemData['dosage'];

                    if ($quantityToDeduct > 0) {
                        $this->inventoryItemRepository->decreaseQuantity(
                            $inventoryItem['id'],
                            $quantityToDeduct,
                            $_SESSION['user']['id'] ?? null,
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
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $prescription = $this->prescriptionRepository->findById($id);

        if (!$prescription) {
            http_response_code(404);
            echo "Рецепт не знайдено";
            return;
        }

        View::render('@modules/Prescription/templates/show.html.twig', [
            'prescription' => $prescription,
        ]);
    }
}
