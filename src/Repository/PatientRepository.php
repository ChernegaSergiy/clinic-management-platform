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
        $sql = "INSERT INTO patients (first_name, last_name, middle_name, birth_date, gender, phone, email, address, tax_id, document_id, marital_status) 
                VALUES (:first_name, :last_name, :middle_name, :birth_date, :gender, :phone, :email, :address, :tax_id, :document_id, :marital_status)";
        
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
}
