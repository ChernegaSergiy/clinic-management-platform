<?php

namespace App\Controller;

use App\Core\View;
use App\Core\Validator;
use App\Repository\ContractRepository;

class ContractController
{
    private ContractRepository $contractRepository;

    public function __construct()
    {
        $this->contractRepository = new ContractRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $contracts = $this->contractRepository->findAll();
        View::render('billing/contracts/index.html.twig', ['contracts' => $contracts]);
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        View::render('billing/contracts/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function store(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'title' => ['required'],
            'start_date' => ['required', 'date'],
            'status' => ['required', 'in:active,expired,terminated'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /billing/contracts/new');
            exit();
        }

        // Handle file upload for contract document
        $filePath = null;
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/contracts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $filename = uniqid('contract_', true) . '_' . basename($_FILES['contract_file']['name']);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['contract_file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/contracts/' . $filename; // Store relative path
            }
        }

        $data = $_POST;
        $data['file_path'] = $filePath;

        $this->contractRepository->save($data);
        $_SESSION['success_message'] = "Контракт успішно створено.";
        header('Location: /billing/contracts');
        exit();
    }

    public function show(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $contract = $this->contractRepository->findById($id);

        if (!$contract) {
            http_response_code(404);
            echo "Контракт не знайдено";
            return;
        }

        View::render('billing/contracts/show.html.twig', ['contract' => $contract]);
    }

    public function edit(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $contract = $this->contractRepository->findById($id);

        if (!$contract) {
            http_response_code(404);
            echo "Контракт не знайдено";
            return;
        }

        View::render('billing/contracts/edit.html.twig', [
            'contract' => $contract,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function update(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $contract = $this->contractRepository->findById($id);

        if (!$contract) {
            http_response_code(404);
            echo "Контракт не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'title' => ['required'],
            'start_date' => ['required', 'date'],
            'status' => ['required', 'in:active,expired,terminated'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /billing/contracts/edit?id=' . $id);
            exit();
        }

        // Handle file upload for contract document (if new file is uploaded)
        $filePath = $contract['file_path']; // Keep existing path by default
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/contracts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $filename = uniqid('contract_', true) . '_' . basename($_FILES['contract_file']['name']);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['contract_file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/contracts/' . $filename; // Store relative path
                // Optionally, delete old file
            }
        }

        $data = $_POST;
        $data['file_path'] = $filePath;

        $this->contractRepository->update($id, $data);
        $_SESSION['success_message'] = "Контракт успішно оновлено.";
        header('Location: /billing/contracts/show?id=' . $id);
        exit();
    }

    public function delete(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $contract = $this->contractRepository->findById($id);

        if (!$contract) {
            http_response_code(404);
            echo "Контракт не знайдено";
            return;
        }

        // Optionally, delete the physical file
        if ($contract['file_path'] && file_exists(__DIR__ . '/../../' . $contract['file_path'])) {
            unlink(__DIR__ . '/../../' . $contract['file_path']);
        }

        $this->contractRepository->delete($id);
        $_SESSION['success_message'] = "Контракт успішно видалено.";
        header('Location: /billing/contracts');
        exit();
    }

    public function downloadFile(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $contract = $this->contractRepository->findById($id);

        if (!$contract || !$contract['file_path'] || !file_exists(__DIR__ . '/../../' . $contract['file_path'])) {
            http_response_code(404);
            echo "Файл контракту не знайдено";
            return;
        }

        $filePath = __DIR__ . '/../../' . $contract['file_path'];
        $filename = basename($contract['file_path']);
        $mimeType = mime_content_type($filePath);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    }
}