<?php

namespace App\Bundles\BillingBundle\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT s.*, sc.name as category_name, s.duration_minutes
            FROM services s
            LEFT JOIN service_categories sc ON s.category_id = sc.id
            ORDER BY s.name ASC
        ";
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT s.*, sc.name as category_name, s.duration_minutes
            FROM services s
            LEFT JOIN service_categories sc ON s.category_id = sc.id
            WHERE s.id = :id
        ";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function save(array $data) : ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO services (name, description, price, category_id, is_active) 
                VALUES (:name, :description, :price, :category_id, :is_active)";

        $success = $conn->executeStatement($sql, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'category_id' => $data['category_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]) > 0;

        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE services SET 
                    name = :name, 
                    description = :description, 
                    price = :price, 
                    category_id = :category_id, 
                    is_active = :is_active 
                WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'category_id' => $data['category_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM services WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }

    public function findCategories() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, name, description FROM service_categories ORDER BY name ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function saveCategory(array $data) : ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO service_categories (name, description) VALUES (:name, :description)";

        $success = $conn->executeStatement($sql, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]) > 0;

        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function findCategoryById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM service_categories WHERE id = :id";

        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function updateCategory(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE service_categories SET name = :name, description = :description WHERE id = :id";

        return $conn->executeStatement($sql, [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]) > 0;
    }

    public function deleteCategory(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM service_categories WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
