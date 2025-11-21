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
}
