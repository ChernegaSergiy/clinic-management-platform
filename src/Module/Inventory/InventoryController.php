<?php

namespace App\Module\Inventory;

use App\Core\View;
use App\Module\Inventory\Repository\InventoryItemRepository;
use App\Core\AuthGuard;

class InventoryController
{
    private InventoryItemRepository $inventoryItemRepository;

    public function __construct()
    {
        $this->inventoryItemRepository = new InventoryItemRepository();
    }

    public function index(): void
    {
        AuthGuard::check();
        $items = $this->inventoryItemRepository->findAll();
        $lowStockItems = $this->inventoryItemRepository->findItemsBelowMinStock();
        $overStockedItems = $this->inventoryItemRepository->findItemsAboveMaxStock();

        View::render('@modules/Inventory/templates/index.html.twig', [
            'items' => $items,
            'lowStockItems' => $lowStockItems,
            'overStockedItems' => $overStockedItems,
        ]);
    }

    public function create(): void
    {
        AuthGuard::check();
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/Inventory/templates/new.html.twig', [
            'old' => $old,
            'errors' => $errors,
            'min_stock_level' => $old['min_stock_level'] ?? 0,
            'max_stock_level' => $old['max_stock_level'] ?? 0,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            http_response_code(404);
            echo "Позицію складу не знайдено";
            return;
        }

        $movementHistory = $this->inventoryItemRepository->getMovementHistory($id);

        View::render('@modules/Inventory/templates/show.html.twig', [
            'item' => $item,
            'movementHistory' => $movementHistory,
        ]);
    }

    public function edit(): void
    {
        AuthGuard::check();

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

        View::render('@modules/Inventory/templates/edit.html.twig', [
            'item' => $item,
            'old' => $old,
            'errors' => $errors,
            'min_stock_level' => $old['min_stock_level'] ?? $item['min_stock_level'],
            'max_stock_level' => $old['max_stock_level'] ?? $item['max_stock_level'],
        ]);
    }

    public function update(): void
    {
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            http_response_code(404);
            echo "Позицію складу не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new \App\Core\Validator(\App\Database::getInstance());
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
