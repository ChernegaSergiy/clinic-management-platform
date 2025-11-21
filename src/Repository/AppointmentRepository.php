<?php

namespace App\Repository;

use App\Database;
use PDO;

class AppointmentRepository implements AppointmentRepositoryInterface
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
                a.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                CONCAT(u.last_name, ' ', u.first_name) as doctor_name,
                a.start_time, 
                a.end_time, 
                a.status
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            ORDER BY a.start_time DESC
        ");
        return $stmt->fetchAll();
    }
}
