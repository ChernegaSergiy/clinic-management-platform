<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\AppointmentRepository;
use App\Repository\IcdCodeRepository;
use App\Repository\LabOrderRepository;
use App\Repository\MedicalRecordRepository;
use App\Core\AttachmentService;
use App\Core\AuditLogger;

class MedicalRecordController
{
    private MedicalRecordRepository $medicalRecordRepository;
    private AppointmentRepository $appointmentRepository;
    private LabOrderRepository $labOrderRepository;
    private IcdCodeRepository $icdCodeRepository;
    private AttachmentService $attachmentService;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->labOrderRepository = new LabOrderRepository();
        $this->icdCodeRepository = new IcdCodeRepository();
        $this->attachmentService = new AttachmentService();
        $this->auditLogger = new AuditLogger();
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
        $attachments = $this->attachmentService->getAttachmentsForEntity('medical_record', $id);

        View::render('medical_records/show.html.twig', [
            'record' => $record,
            'lab_orders' => $labOrders,
            'attachments' => $attachments,
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

    public function uploadAttachment(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $medicalRecordId = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($medicalRecordId);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileData = [
                        'name' => $name,
                        'type' => $_FILES['attachments']['type'][$key],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                        'error' => $_FILES['attachments']['error'][$key],
                        'size' => $_FILES['attachments']['size'][$key],
                    ];
                    $this->attachmentService->uploadAttachment($fileData, 'medical_record', $medicalRecordId, $_SESSION['user']['id'] ?? null);
                }
            }
        }

        header('Location: /medical-records/show?id=' . $medicalRecordId);
        exit();
    }

    public function downloadAttachment(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $medicalRecordId = (int)($_GET['record_id'] ?? 0);
        $attachmentId = (int)($_GET['attachment_id'] ?? 0);

        $attachment = $this->attachmentService->getAttachmentById($attachmentId);

        if (!$attachment || $attachment['entity_type'] !== 'medical_record' || $attachment['entity_id'] !== $medicalRecordId) {
            http_response_code(404);
            echo "Вкладення не знайдено або доступ заборонено";
            return;
        }

        // Check ACL (simplified for now, assuming only uploader can download or public)
        // More complex ACL check would go here, using AttachmentService::checkViewAccess
        $fullPath = __DIR__ . '/../../uploads/' . $attachment['filepath'];

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Файл не знайдено на сервері";
            return;
        }

        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['filename'] . '"');
        header('Content-Length: ' . $attachment['size']);
        readfile($fullPath);
        exit();
    }
}
