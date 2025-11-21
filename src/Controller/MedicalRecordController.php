<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\AppointmentRepository;
use App\Repository\IcdCodeRepository;
use App\Repository\LabOrderRepository;
use App\Repository\MedicalRecordRepository;
use App\Core\AttachmentService;

class MedicalRecordController
{
    private MedicalRecordRepository $medicalRecordRepository;
    private AppointmentRepository $appointmentRepository;
    private LabOrderRepository $labOrderRepository;
    private IcdCodeRepository $icdCodeRepository;
    private AttachmentService $attachmentService;

    public function __construct()
    {
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->labOrderRepository = new LabOrderRepository();
        $this->icdCodeRepository = new IcdCodeRepository();
        $this->attachmentService = new AttachmentService();
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        View::render('medical_records/new.html.twig', ['appointment' => $appointment]);
    }

    public function store(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        $data = $_POST;
        $data['patient_id'] = $appointment['patient_id'];
        $data['appointment_id'] = $appointmentId;
        $data['doctor_id'] = $appointment['doctor_id'];
        
        $medicalRecordId = $this->medicalRecordRepository->save($data);

        if ($medicalRecordId && !empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileData = [
                        'name' => $name,
                        'type' => $_FILES['attachments']['type'][$key],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                        'error' => $_FILES['attachments']['error'][$key],
                        'size' => $_FILES['attachments']['size'][$key],
                    ];
                    // Assuming current user ID is available in session
                    $this->attachmentService->uploadAttachment($fileData, 'medical_record', $medicalRecordId, $_SESSION['user']['id'] ?? null);
                }
            }
        }

        // Оновлюємо статус запису на "виконано"
        $this->appointmentRepository->updateStatus($appointmentId, 'completed');

        header('Location: /patients/show?id=' . $appointment['patient_id']);
        exit();
    }

    public function show(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        $labOrders = $this->labOrderRepository->findByMedicalRecordId($id);

        View::render('medical_records/show.html.twig', [
            'record' => $record,
            'lab_orders' => $labOrders,
        ]);
    }

    public function getIcdCodes(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->icdCodeRepository->searchByCodeOrDescription($searchTerm);
        
        header('Content-Type: application/json');
        echo json_encode($codes);
    }
}
