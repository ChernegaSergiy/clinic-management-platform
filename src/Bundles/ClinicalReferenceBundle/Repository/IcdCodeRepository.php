<?php

namespace App\Bundles\ClinicalReferenceBundle\Repository;

use App\Entity\IcdCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IcdCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcdCode::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, code, description FROM icd_codes ORDER BY code ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function countAll() : int
    {
        $conn = $this->getEntityManager()->getConnection();
        return (int)$conn->fetchOne("SELECT COUNT(*) FROM icd_codes");
    }

    public function replaceAll(array $rows) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $conn->executeStatement("DELETE FROM icd_codes");

            $sql = "INSERT INTO icd_codes (code, description) VALUES (:code, :description)";

            $count = 0;
            $seen = [];
            foreach ($rows as $row) {
                $code = trim($row['code'] ?? '');
                if ('' === $code || '-' === $code) {
                    continue; // пропускаємо пусті/технічні коди
                }
                if (isset($seen[$code])) {
                    continue; // уникаємо дублювання
                }
                $description = $row['description'] ?? '';

                $conn->executeStatement($sql, [
                    'code' => $code,
                    'description' => $description,
                ]);
                $seen[$code] = true;
                $count++;
            }
            $conn->commit();
            return $count;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function searchByCodeOrDescription(string $searchTerm) : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, code, description FROM icd_codes 
                WHERE code LIKE :term OR description LIKE :term 
                ORDER BY code ASC LIMIT 20";

        return $conn->fetchAllAssociative($sql, ['term' => '%' . $searchTerm . '%']);
    }
}
