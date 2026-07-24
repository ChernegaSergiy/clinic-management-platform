<?php

namespace App\Module\Inventory;

use App\Database\Database;
use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Module\Inventory\Repository\InventoryItemRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class InventoryController
{
    private InventoryItemRepositoryInterface $inventoryItemRepository;

    public function __construct(InventoryItemRepositoryInterface $inventoryItemRepository)
    {
        $this->inventoryItemRepository = $inventoryItemRepository;
    }

    #[Route('/inventory', name: 'inventory_index', methods: ['GET'])]
    public function index(): void
    {
        AuthGuard::check();
        Gate::authorize('inventory.manage');
        $searchTerm = $_GET['search'] ?? '';
        $items = $this->inventoryItemRepository->findAll($searchTerm);
        $lowStockItems = $this->inventoryItemRepository->findItemsBelowMinStock();
        $overStockedItems = $this->inventoryItemRepository->findItemsAboveMaxStock();

        View::render('@modules/Inventory/templates/index.html.twig', [
            'items' => $items,
            'lowStockItems' => $lowStockItems,
            'overStockedItems' => $overStockedItems,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/inventory/new', name: 'inventory_new_create', methods: ['GET'])]
    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('inventory.manage');
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

    #[Route('/inventory/new', name: 'inventory_new_store', methods: ['POST'])]
    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('inventory.manage');

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
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

    #[Route('/inventory/show', name: 'inventory_show_show', methods: ['GET'])]
    public function show(): void
    {
        AuthGuard::check();
        Gate::authorize('inventory.manage');

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

    #[Route('/inventory/edit', name: 'inventory_edit_edit', methods: ['GET'])]
    public function edit(): void
    {
        AuthGuard::check();
        Gate::authorize('inventory.manage');

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

    #[Route('/inventory/edit', name: 'inventory_edit_update', methods: ['POST'])]
    public function update(): void
    {
        AuthGuard::check();
        Gate::authorize('inventory.manage');

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            http_response_code(404);
            echo "Позицію складу не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new \App\Core\Validation\Validator(Database::getInstance());
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
