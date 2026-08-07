<?php

namespace App\Bundles\PrescriptionBundle\Controller;

use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepositoryInterface;
use App\Bundles\PatientBundle\Repository\PatientRepositoryInterface;
use App\Bundles\PrescriptionBundle\Repository\PrescriptionRepository;
use App\Bundles\UserBundle\Repository\UserRepositoryInterface;
use App\Core\Validation\Validator;
use App\Module\Inventory\Repository\InventoryItemRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/prescriptions', name: 'prescription_index', methods: ['GET'])]
    public function index() : Response
    {
        $this->checkAuth();
        $searchTerm = $_GET['search'] ?? '';
        $user = $this->gate->getUser();
        $prescriptions = [];

        if ($this->gate->allows('prescription.view.any')) {
            $prescriptions = $this->prescriptionRepository->findAll($searchTerm);
        } elseif ($this->gate->allows('prescription.view.own')) {
            if ($user && $user->getId()) {
                $prescriptions = $this->prescriptionRepository->findByDoctorId($user->getId(), $searchTerm);
            }
        }

        return $this->render('@Prescription/index.html.twig', [
            'prescriptions' => $prescriptions,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/prescriptions/new', name: 'prescription_new', methods: ['GET'])]
    public function create() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('prescription.create', ['doctor_id' => $this->gate->getUser()->getId()]); // Check if current user can create prescriptions

        $patientId = (int)($_GET['patient_id'] ?? 0);
        $patient = $this->patientRepository->findById($patientId);

        if (!$patient) {
            return new Response("Пацієнта не знайдено", 404);
        }

        $doctors = $this->userRepository->findAllDoctors();
        $medicalRecords = $this->medicalRecordRepository->findByPatientId($patientId);

        $doctorOptions = [];
        foreach ($doctors as $doctor) {
            $doctorOptions[$doctor['id']] = $doctor['full_name'];
        }

        return $this->render('@Prescription/new.html.twig', [
            'patient' => $patient,
            'doctors' => $doctorOptions,
            'medicalRecords' => $medicalRecords,
            'currentDoctorId' => $this->gate->getUser()->getId(),
        ]);
    }

    #[Route('/prescriptions/new', name: 'prescription_store', methods: ['POST'])]
    public function store() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('prescription.create', ['doctor_id' => $_POST['doctor_id'] ?? null]);

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

            return $this->render('@Prescription/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'patient' => $patient,
                'doctors' => $doctorOptions,
                'medicalRecords' => $medicalRecords,
                'currentDoctorId' => $this->gate->getUser()->getId(),
            ]);
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
                            $this->gate->getUser()->getId(),
                            'Виконання рецепту #' . $prescriptionId
                        );
                    }
                }
            }
        }
        return new RedirectResponse('/patients/show?id=' . $_POST['patient_id']);
    }

    #[Route('/prescriptions/show', name: 'prescription_show', methods: ['GET'])]
    public function show() : Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->gate->authorize('prescription.view', ['id' => $id]);

        $prescription = $this->prescriptionRepository->findById($id);

        if (!$prescription) {
            return new Response("Рецепт не знайдено", 404);
        }

        return $this->render('@Prescription/show.html.twig', [
            'prescription' => $prescription,
        ]);
    }
}
