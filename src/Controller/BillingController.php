<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\InvoiceRepository;

class BillingController
{
    private InvoiceRepository $invoiceRepository;

    public function __construct()
    {
        $this->invoiceRepository = new InvoiceRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $invoices = $this->invoiceRepository->findAll();
        View::render('billing/index.html.twig', ['invoices' => $invoices]);
    }
}
