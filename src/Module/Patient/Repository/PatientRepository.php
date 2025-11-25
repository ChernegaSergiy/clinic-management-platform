<?php

namespace App\Module\Patient\Repository;

use App\Database;
use PDO;
use App\Core\AuditLogger;

class PatientRepository implements PatientRepositoryInterface
{
    private PDO $pdo;
    private ?string $lastError = null;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(string $searchTerm = ''): array
    {
        $sql = "SELECT * FROM patients";
        $params = [];

        if (!empty($searchTerm)) {
            // Use full-text search for relevant columns
            $sql .= " WHERE MATCH(first_name, last_name, middle_name, address) AGAINST (:searchTerm IN BOOLEAN MODE)";
            $params[':searchTerm'] = $searchTerm . '*'; // Adding wildcard for partial matches

            // Fallback for other fields if full-text index doesn't cover all search needs or for older MySQL versions
            // Uncomment and adjust if needed
            // $sql .= " OR last_name LIKE :term OR first_name LIKE :term OR phone LIKE :term";
            // $params[':term'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY last_name, first_name";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $this->lastError = null;

        // Check for duplicate patient before saving
        if ($this->findByCredentials($data['last_name'], $data['first_name'], $data['birth_date'])) {
            // Patient with same first name, last name, and birth date already exists
            $this->lastError = 'patient_exists';
            return false;
        }

        if (!empty($data['tax_id']) && $this->findByTaxId($data['tax_id'])) {
            $this->lastError = 'tax_id_exists';
            return false;
        }

        $sql = "INSERT INTO patients (first_name, last_name, middle_name, birth_date, gender, 
                                    phone, email, address, tax_id, document_id, marital_status, status) 
                VALUES (:first_name, :last_name, :middle_name, :birth_date, :gender, 
                        :phone, :email, :address, :tax_id, :document_id, :marital_status, :status)";

        $stmt = $this->pdo->prepare($sql);

        try {
            return $stmt->execute(
                [
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
                ]
            );
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->lastError = 'duplicate_key';
                return false;
            }
            throw $e;
        }
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

    public function findByTaxId(string $taxId, ?int $excludeId = null): ?array
    {
        $sql = "SELECT * FROM patients WHERE tax_id = :tax_id";
        $params = [':tax_id' => $taxId];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function update(int $id, array $data): bool
    {
        $this->lastError = null;

        $oldPatient = $this->findById($id);
        $oldStatus = $oldPatient['status'] ?? null;

        if (!empty($data['tax_id']) && $this->findByTaxId($data['tax_id'], $id)) {
            $this->lastError = 'tax_id_exists';
            return false;
        }

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

        try {
            $success = $stmt->execute(
                [
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
                ]
            );
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->lastError = 'duplicate_key';
                return false;
            }
            throw $e;
        }

        if ($success && $oldStatus !== ($data['status'] ?? 'active')) {
            $auditLogger = new AuditLogger();
            // Assuming current user ID is available, for now, null or placeholder
            $auditLogger->log('patient', $id, 'status_change', $oldStatus, $data['status'] ?? 'active');
        }

        return $success;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function findAllActive(): array
    {
        $stmt = $this->pdo->query("SELECT id, CONCAT(last_name, ' ', first_name) as full_name FROM patients WHERE status = 'active' ORDER BY last_name, first_name");
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $oldPatient = $this->findById($id);
        $oldStatus = $oldPatient['status'] ?? null;

        $stmt = $this->pdo->prepare("UPDATE patients SET status = :status WHERE id = :id");
        $success = $stmt->execute([':status' => $status, ':id' => $id]);

        if ($success && $oldStatus !== $status) {
            $auditLogger = new AuditLogger();
            $auditLogger->log('patient', $id, 'status_change', $oldStatus, $status);
        }
        return $success;
    }
}
