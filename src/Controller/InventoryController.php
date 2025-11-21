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
        $lowStockItems = $this->inventoryItemRepository->findItemsBelowMinStock();
        $overStockedItems = $this->inventoryItemRepository->findItemsAboveMaxStock();

        View::render('inventory/index.html.twig', [
            'items' => $items,
            'lowStockItems' => $lowStockItems,
            'overStockedItems' => $overStockedItems,
        ]);
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('inventory/new.html.twig', [
            'old' => $old,
            'errors' => $errors,
            'min_stock_level' => $old['min_stock_level'] ?? 0,
            'max_stock_level' => $old['max_stock_level'] ?? 0,
        ]);
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

        $movementHistory = $this->inventoryItemRepository->getMovementHistory($id);

        View::render('inventory/show.html.twig', [
            'item' => $item,
            'movementHistory' => $movementHistory,
        ]);
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

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('inventory/edit.html.twig', [
            'item' => $item,
            'old' => $old,
            'errors' => $errors,
            'min_stock_level' => $old['min_stock_level'] ?? $item['min_stock_level'],
            'max_stock_level' => $old['max_stock_level'] ?? $item['max_stock_level'],
        ]);
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
