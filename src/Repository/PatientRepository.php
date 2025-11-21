<?php

namespace App\Repository;

use App\Database;
use PDO;

class PatientRepository implements PatientRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(string $searchTerm = ''): array
    {
        $sql = "SELECT id, CONCAT(last_name, ' ', first_name) as name, birth_date, phone FROM patients";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE last_name LIKE :term OR first_name LIKE :term OR phone LIKE :term";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY last_name, first_name";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        // Check for duplicate patient before saving
        if ($this->findByCredentials($data['last_name'], $data['first_name'], $data['birth_date'])) {
            // Patient with same first name, last name, and birth date already exists
            return false;
        }

        $sql = "INSERT INTO patients (first_name, last_name, middle_name, birth_date, gender, phone, email, address, tax_id, document_id, marital_status, status) 
                VALUES (:first_name, :last_name, :middle_name, :birth_date, :gender, :phone, :email, :address, :tax_id, :document_id, :marital_status, :status)";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':birth_date' => $data['birth_date'],
            ':gender' => $data['gender'],
            ':phone' => $data['phone'],
            ':email' => $data['email'] ?? null,
            ':address' => $data['address'] ?? null,
            ':tax_id' => $data['tax_id'] ?? null,
            ':document_id' => $data['document_id'] ?? null,
            ':marital_status' => $data['marital_status'] ?? null,
            ':status' => $data['status'] ?? 'active',
        ]);
    }

    public function findByCredentials(string $lastName, string $firstName, string $birthDate): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE last_name = :last_name AND first_name = :first_name AND birth_date = :birth_date");
        $stmt->execute([
            ':last_name' => $lastName,
            ':first_name' => $firstName,
            ':birth_date' => $birthDate,
        ]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE patients SET 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    middle_name = :middle_name, 
                    birth_date = :birth_date, 
                    gender = :gender, 
                    phone = :phone, 
                    email = :email, 
                    address = :address, 
                    tax_id = :tax_id, 
                    document_id = :document_id, 
                    marital_status = :marital_status,
                    status = :status
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':birth_date' => $data['birth_date'],
            ':gender' => $data['gender'],
            ':phone' => $data['phone'],
            ':email' => $data['email'] ?? null,
            ':address' => $data['address'] ?? null,
            ':tax_id' => $data['tax_id'] ?? null,
            ':document_id' => $data['document_id'] ?? null,
            ':marital_status' => $data['marital_status'] ?? null,
            ':status' => $data['status'] ?? 'active',
        ]);
    }

    public function findAllActive(): array
    {
        $stmt = $this->pdo->query("SELECT id, CONCAT(last_name, ' ', first_name) as full_name FROM patients WHERE status = 'active' ORDER BY last_name, first_name");
        return $stmt->fetchAll();
    }
}
