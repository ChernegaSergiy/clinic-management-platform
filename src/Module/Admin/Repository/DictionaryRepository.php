<?php

namespace App\Module\Admin\Repository;

use App\Entity\Dictionary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DictionaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dictionary::class);
    }

    // --- Dictionary Definitions ---
    public function findAll(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM dictionaries ORDER BY name ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM dictionaries WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function save(array $data): ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO dictionaries (name, description, type) VALUES (:name, :description, :type)";
        
        $success = $conn->executeStatement($sql, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? null,
        ]) > 0;
        
        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function update(int $id, array $data): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE dictionaries SET name = :name, description = :description, type = :type WHERE id = :id";
        
        return $conn->executeStatement($sql, [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? null,
        ]) > 0;
    }

    public function delete(int $id): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM dictionaries WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }

    // --- Dictionary Values ---
    public function findValuesByDictionaryId(int $dictionaryId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM dictionary_values 
                WHERE dictionary_id = :dictionary_id 
                ORDER BY order_num ASC, label ASC";
                
        return $conn->fetchAllAssociative($sql, ['dictionary_id' => $dictionaryId]);
    }

    public function findValueById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM dictionary_values WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function saveValue(array $data): ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO dictionary_values (dictionary_id, value, label, order_num, is_active) 
                VALUES (:dictionary_id, :value, :label, :order_num, :is_active)";
                
        $success = $conn->executeStatement($sql, [
            'dictionary_id' => $data['dictionary_id'],
            'value' => $data['value'],
            'label' => $data['label'],
            'order_num' => $data['order_num'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]) > 0;
        
        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function updateValue(int $id, array $data): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE dictionary_values SET 
                    dictionary_id = :dictionary_id, 
                    value = :value, 
                    label = :label, 
                    order_num = :order_num, 
                    is_active = :is_active 
                WHERE id = :id";
                
        return $conn->executeStatement($sql, [
            'id' => $id,
            'dictionary_id' => $data['dictionary_id'],
            'value' => $data['value'],
            'label' => $data['label'],
            'order_num' => $data['order_num'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]) > 0;
    }

    public function deleteValue(int $id): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM dictionary_values WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
