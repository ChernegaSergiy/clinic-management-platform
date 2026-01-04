<?php

namespace App\Module\MedicalRecord;

use App\Database;
use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Core\Service\AttachmentService;
use App\Core\Service\AuditLogger;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\ClinicalReference\Repository\IcdCodeRepository;
use App\Module\ClinicalReference\Repository\InterventionCodeRepository;
use App\Module\LabOrder\Repository\LabOrderRepository;
use App\Module\MedicalRecord\Repository\MedicalRecordRepository;

class MedicalRecordController
{
    private MedicalRecordRepository $medicalRecordRepository;
    private AppointmentRepository $appointmentRepository;
    private LabOrderRepository $labOrderRepository;
    private IcdCodeRepository $icdCodeRepository;
    private InterventionCodeRepository $interventionCodeRepository;
    private AttachmentService $attachmentService;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->labOrderRepository = new LabOrderRepository();
        $this->icdCodeRepository = new IcdCodeRepository();
        $this->interventionCodeRepository = new InterventionCodeRepository();
        $this->attachmentService = new AttachmentService();
        $this->auditLogger = new AuditLogger();
    }

    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('medical_record.create');

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

    public function index(): void
    {
        AuthGuard::check();
        $user = Gate::getUser();
        $searchTerm = $_GET['search'] ?? '';
        $records = [];

        if (Gate::allows('medical_record.view.any')) {
            $records = $this->medicalRecordRepository->findAll($searchTerm);
        } elseif (Gate::allows('medical_record.view.own')) {
            if ($user && $user->getId()) {
                $records = $this->medicalRecordRepository->findByDoctorId($user->getId(), $searchTerm);
            }
        }

        View::render('@modules/MedicalRecord/templates/index.html.twig', [
            'records' => $records,
            'searchTerm' => $searchTerm,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('medical_record.create');

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        if (!empty($_POST['visit_date'])) {
            try {
                $dt = new \DateTime($_POST['visit_date']);
                $_POST['visit_date'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
            }
        }

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
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
                    $this->attachmentService->uploadAttachment(
                        $fileData,
                        'medical_record',
                        $medicalRecordId,
                        Gate::getUser()->getId()
                    );
                }
            }
        }

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

        Gate::authorize('medical_record.view', ['id' => $id]);

        $this->auditLogger->log(
            'medical_record',
            $id,
            'view',
            null,
            null,
            Gate::getUser()->getId()
        );

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
        Gate::authorize('clinical.manage');

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->icdCodeRepository->searchByCodeOrDescription($searchTerm);

        header('Content-Type: application/json');
        echo json_encode($codes);
    }

    public function getInterventionCodes(): void
    {
        AuthGuard::check();
        Gate::authorize('clinical.manage');

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->interventionCodeRepository->searchByCodeOrDescription($searchTerm);

        header('Content-Type: application/json');
        echo json_encode($codes);
    }

    public function edit(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        Gate::authorize('medical_record.edit', ['id' => $id]);

        View::render('@modules/MedicalRecord/templates/edit.html.twig', [
            'record' => $record,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function update(): void
    {
        AuthGuard::check();
        $id = (int)($_POST['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        Gate::authorize('medical_record.edit', ['id' => $id]);

        if (!empty($_POST['visit_date'])) {
            try {
                $dt = new \DateTime($_POST['visit_date']);
                $_POST['visit_date'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
            }
        }

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
        $validator->validate(
            $_POST,
            [
                'diagnosis_code' => ['required'],
                'visit_date' => ['required', 'datetime'],
                'icd_codes' => ['array'],
                'intervention_codes' => ['array'],
            ]
        );

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /medical-records/edit?id=' . $id);
            exit();
        }

        $data = $_POST;
        $data['patient_id'] = $record['patient_id'];
        $data['appointment_id'] = $record['appointment_id'];
        $data['doctor_id'] = $record['doctor_id'];

        $this->medicalRecordRepository->update(
            $id,
            $data
        );

        header('Location: /medical-records/show?id=' . $id);
        exit();
    }

    public function uploadAttachment(): void
    {
        AuthGuard::check();
        $medicalRecordId = (int)($_POST['medical_record_id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($medicalRecordId);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        Gate::authorize('medical_record.edit', ['id' => $medicalRecordId]);

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
                    $this->attachmentService->uploadAttachment(
                        $fileData,
                        'medical_record',
                        $medicalRecordId,
                        Gate::getUser()->getId()
                    );
                }
            }
        }

        header('Location: /medical-records/show?id=' . $medicalRecordId);
        exit();
    }

    public function downloadAttachment(): void
    {
        AuthGuard::check();
        $attachmentId = (int)($_GET['attachment_id'] ?? 0);
        $attachment = $this->attachmentService->getAttachmentById($attachmentId);

        if (!$attachment || $attachment['entity_type'] !== 'medical_record') {
            http_response_code(404);
            echo "Вкладення не знайдено";
            return;
        }

        $medicalRecordId = (int)$attachment['entity_id'];
        $record = $this->medicalRecordRepository->findById($medicalRecordId);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис, пов'язаний із вкладенням, не знайдено";
            return;
        }

        Gate::authorize('medical_record.view', ['id' => $medicalRecordId]);

        $uploadBase = dirname(__DIR__, 3) . '/uploads/';
        $candidates = [];
        if (!empty($attachment['filepath'])) {
            $candidates[] = $uploadBase . ltrim($attachment['filepath'], '/');
        }
        $path = $uploadBase . 'medical_record/' . $medicalRecordId . '/';
        $path .= ($attachment['filename'] ?? '');
        $candidates[] = $path;

        $fullPath = null;
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $fullPath = $path;
                break;
            }
        }

        if (!$fullPath) {
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
