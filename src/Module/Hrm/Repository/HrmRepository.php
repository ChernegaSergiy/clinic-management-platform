<?php

namespace App\Module\Hrm\Repository;

use App\Database\Database;
use PDO;

class HrmRepository implements HrmRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $sql = "SELECT e.*, d.name as department_name 
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                ORDER BY e.last_name, e.first_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO employees (first_name, last_name, middle_name, position, department_id, hire_date, salary, contact_phone, status, user_id) 
                VALUES (:first_name, :last_name, :middle_name, :position, :department_id, :hire_date, :salary, :contact_phone, :status, :user_id)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':position' => $data['position'],
            ':department_id' => $data['department_id'] ?? null,
            ':hire_date' => $data['hire_date'],
            ':salary' => $data['salary'] ?: null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':status' => $data['status'] ?? 'active',
            ':user_id' => $data['user_id'] ?: null,
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT e.*, d.name as department_name 
                                       FROM employees e 
                                       LEFT JOIN departments d ON e.department_id = d.id 
                                       WHERE e.id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE employees SET
                    first_name = :first_name,
                    last_name = :last_name,
                    middle_name = :middle_name,
                    position = :position,
                    department_id = :department_id,
                    hire_date = :hire_date,
                    fire_date = :fire_date,
                    salary = :salary,
                    contact_phone = :contact_phone,
                    status = :status,
                    user_id = :user_id
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':position' => $data['position'],
            ':department_id' => $data['department_id'] ?? null,
            ':hire_date' => $data['hire_date'],
            ':fire_date' => empty($data['fire_date']) ? null : $data['fire_date'],
            ':salary' => $data['salary'] ?: null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':status' => $data['status'] ?? 'active',
            ':user_id' => $data['user_id'] ?: null,
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE employees SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function findByDepartment(int $departmentId): array
    {
        $sql = "
            SELECT 
                e.*, 
                d.name as department_name, 
                dp.name as parent_name,
                u.email as user_email
            FROM employees e 
            LEFT JOIN departments d ON e.department_id = d.id 
            LEFT JOIN departments dp ON d.parent_id = dp.id 
            LEFT JOIN users u ON e.user_id = u.id
            WHERE e.department_id = :department_id 
            ORDER BY e.last_name, e.first_name
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll();
    }
}
