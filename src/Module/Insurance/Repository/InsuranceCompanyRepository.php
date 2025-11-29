<?php

declare(strict_types=1);

namespace App\Module\Insurance\Repository;

use App\Database;

class InsuranceCompanyRepository
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->database->getConnection()->prepare("SELECT * FROM insurance_companies WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->database->getConnection()->prepare("SELECT * FROM insurance_companies");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(string $name, ?string $contactPerson = null, ?string $phone = null, ?string $email = null, ?string $notes = null): int
    {
        $stmt = $this->database->getConnection()->prepare("
            INSERT INTO insurance_companies (name, contact_person, phone, email, notes, created_at, updated_at)
            VALUES (:name, :contact_person, :phone, :email, :notes, NOW(), NOW())
        ");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact_person', $contactPerson);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':notes', $notes);
        $stmt->execute();
        return (int) $this->database->getConnection()->lastInsertId();
    }

    public function update(int $id, string $name, ?string $contactPerson = null, ?string $phone = null, ?string $email = null, ?string $notes = null): bool
    {
        $stmt = $this->database->getConnection()->prepare("
            UPDATE insurance_companies
            SET name = :name, contact_person = :contact_person, phone = :phone, email = :email, notes = :notes, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact_person', $contactPerson);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':notes', $notes);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->database->getConnection()->prepare("DELETE FROM insurance_companies WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
