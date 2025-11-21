<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\InventoryItemRepository;

class InventoryController
{
    private InventoryItemRepository $inventoryItemRepository;

    public function __construct()
    {
        $this->inventoryItemRepository = new InventoryItemRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $items = $this->inventoryItemRepository->findAll();
        View::render('inventory/index.html.twig', ['items' => $items]);
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        View::render('inventory/new.html.twig');
    }

    public function store(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $validator = new Validator();
        $validator->validate($_POST, [
            'name' => ['required'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'cost' => ['numeric', 'min:0'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /inventory/new');
            exit();
        }

        $this->inventoryItemRepository->save($_POST);
        $_SESSION['success_message'] = "Позицію складу успішно додано.";
        header('Location: /inventory');
        exit();
    }

    public function show(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            http_response_code(404);
            echo "Позицію складу не знайдено";
            return;
        }

        View::render('inventory/show.html.twig', ['item' => $item]);
    }

    public function edit(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            http_response_code(404);
            echo "Позицію складу не знайдено";
            return;
        }

        View::render('inventory/edit.html.twig', ['item' => $item]);
    }

    public function update(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            http_response_code(404);
            echo "Позицію складу не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new Validator();
        $validator->validate($_POST, [
            'name' => ['required'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'cost' => ['numeric', 'min:0'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /inventory/edit?id=' . $id);
            exit();
        }

        $this->inventoryItemRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Позицію складу успішно оновлено.";
        header('Location: /inventory/show?id=' . $id);
        exit();
    }
}
