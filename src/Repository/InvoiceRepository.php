<?php

namespace App\Repository;

use App\Database;
use PDO;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                i.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                i.amount,
                i.status,
                i.issued_date
            FROM invoices i
            JOIN patients p ON i.patient_id = p.id
            ORDER BY i.issued_date DESC
        ");
        return $stmt->fetchAll();
    }
}
