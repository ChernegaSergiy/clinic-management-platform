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

        // TODO: Add validation
        $this->inventoryItemRepository->save($_POST);
        header('Location: /inventory');
        exit();
    }
}
