<?php

namespace App\Bundles\InventoryBundle\Controller;

use App\Bundles\InventoryBundle\Repository\InventoryItemRepositoryInterface;
use App\Core\Validation\Validator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InventoryController extends \App\Core\Controller\AbstractController
{
    private InventoryItemRepositoryInterface $inventoryItemRepository;
    private Validator $validator;

    public function __construct(InventoryItemRepositoryInterface $inventoryItemRepository, Validator $validator)
    {
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->validator = $validator;
    }

    #[Route('/inventory', name: 'inventory_index', methods: ['GET'])]
    public function index() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('inventory.manage');
        $searchTerm = $_GET['search'] ?? '';
        $items = $this->inventoryItemRepository->findAll($searchTerm);
        $lowStockItems = $this->inventoryItemRepository->findItemsBelowMinStock();
        $overStockedItems = $this->inventoryItemRepository->findItemsAboveMaxStock();

        return $this->render('@Inventory/index.html.twig', [
            'items' => $items,
            'lowStockItems' => $lowStockItems,
            'overStockedItems' => $overStockedItems,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/inventory/new', name: 'inventory_new_create', methods: ['GET'])]
    public function create() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('inventory.manage');
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $this->render('@Inventory/new.html.twig', [
            'old' => $old,
            'errors' => $errors,
            'min_stock_level' => $old['min_stock_level'] ?? 0,
            'max_stock_level' => $old['max_stock_level'] ?? 0,
        ]);
    }

    #[Route('/inventory/new', name: 'inventory_new_store', methods: ['POST'])]
    public function store() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('inventory.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'cost' => ['numeric', 'min:0'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/inventory/new');
        }

        $this->inventoryItemRepository->save($_POST);
        $_SESSION['success_message'] = "Позицію складу успішно додано.";
        return new RedirectResponse('/inventory');
    }

    #[Route('/inventory/show', name: 'inventory_show_show', methods: ['GET'])]
    public function show() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('inventory.manage');

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            return new Response("Позицію складу не знайдено", 404);
        }

        $movementHistory = $this->inventoryItemRepository->getMovementHistory($id);

        return $this->render('@Inventory/show.html.twig', [
            'item' => $item,
            'movementHistory' => $movementHistory,
        ]);
    }

    #[Route('/inventory/edit', name: 'inventory_edit_edit', methods: ['GET'])]
    public function edit() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('inventory.manage');

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            return new Response("Позицію складу не знайдено", 404);
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $this->render('@Inventory/edit.html.twig', [
            'item' => $item,
            'old' => $old,
            'errors' => $errors,
            'min_stock_level' => $old['min_stock_level'] ?? $item['min_stock_level'],
            'max_stock_level' => $old['max_stock_level'] ?? $item['max_stock_level'],
        ]);
    }

    #[Route('/inventory/edit', name: 'inventory_edit_update', methods: ['POST'])]
    public function update() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('inventory.manage');

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->inventoryItemRepository->findById($id);

        if (!$item) {
            return new Response("Позицію складу не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'cost' => ['numeric', 'min:0'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/inventory/edit?id=' . $id);
        }

        $this->inventoryItemRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Позицію складу успішно оновлено.";
        return new RedirectResponse('/inventory/show?id=' . $id);
    }
}
