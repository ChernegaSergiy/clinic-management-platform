<?php

namespace App\Module\Hrm\Repository;

use Api\Database\Database;
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
        $sql = "SELECT * FROM employees ORDER BY last_name, first_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO employees (first_name, last_name, middle_name, position, department, hire_date, salary, contact_phone, status, user_id) 
                VALUES (:first_name, :last_name, :middle_name, :position, :department, :hire_date, :salary, :contact_phone, :status, :user_id)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':position' => $data['position'],
            ':department' => $data['department'] ?? null,
            ':hire_date' => $data['hire_date'],
            ':salary' => $data['salary'] ?: null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':status' => $data['status'] ?? 'active',
            ':user_id' => $data['user_id'] ?: null,
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = :id");
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
                    department = :department,
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
            ':department' => $data['department'] ?? null,
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
}
