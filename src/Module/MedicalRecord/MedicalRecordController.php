<?php

namespace App\Module\MedicalRecord;

use App\Core\View;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\ClinicalReference\Repository\IcdCodeRepository;
use App\Module\LabOrder\Repository\LabOrderRepository;
use App\Module\MedicalRecord\Repository\MedicalRecordRepository;
use App\Core\AttachmentService;
use App\Core\AuditLogger;
use App\Core\AuthGuard;

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
        AuthGuard::check();

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        View::render('@modules/MedicalRecord/templates/new.html.twig', [
            'appointment' => $appointment,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function store(): void
    {
        AuthGuard::check();

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        // Normalize visit_date to DB format
        if (!empty($_POST['visit_date'])) {
            try {
                $dt = new \DateTime($_POST['visit_date']);
                $_POST['visit_date'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // let validator catch it
            }
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'diagnosis_code' => ['required'],
            'visit_date' => ['required', 'datetime'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /medical-records/new?appointment_id=' . $appointmentId);
            exit();
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
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        // Log the view event
        $this->auditLogger->log('medical_record', $id, 'view', null, null, $_SESSION['user']['id'] ?? null);

        $labOrders = $this->labOrderRepository->findByMedicalRecordId($id);
        $attachments = $this->attachmentService->getAttachmentsForEntity('medical_record', $id);

        View::render('@modules/MedicalRecord/templates/show.html.twig', [
            'record' => $record,
            'lab_orders' => $labOrders,
            'attachments' => $attachments,
        ]);
    }

    public function getIcdCodes(): void
    {
        AuthGuard::check();

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->icdCodeRepository->searchByCodeOrDescription($searchTerm);
        
        header('Content-Type: application/json');
        echo json_encode($codes);
    }

    public function uploadAttachment(): void
    {
        AuthGuard::check();

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
        AuthGuard::check();

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
