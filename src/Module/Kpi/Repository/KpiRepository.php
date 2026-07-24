<?php

namespace App\Module\Kpi\Repository;

use App\Entity\KpiDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KpiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KpiDefinition::class);
    }

    // --- KPI Definitions ---
    public function findAllKpiDefinitions(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM kpi_definitions ORDER BY name ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findActiveKpiDefinitions(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM kpi_definitions WHERE is_active = 1 ORDER BY name ASC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findKpiDefinitionById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM kpi_definitions WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function saveKpiDefinition(array $data): ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO kpi_definitions (name, description, kpi_type, target_value, unit, is_active, period) 
                VALUES (:name, :description, :kpi_type, :target_value, :unit, :is_active, :period)";
        
        $success = $conn->executeStatement($sql, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'kpi_type' => $data['kpi_type'],
            'target_value' => $data['target_value'] ?? null,
            'unit' => $data['unit'] ?? null,
            'is_active' => (int)($data['is_active'] ?? true),
            'period' => $data['period'] ?? 'day',
        ]) > 0;
        
        return $success ? (int)$conn->lastInsertId() : null;
    }

    public function updateKpiDefinition(int $id, array $data): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE kpi_definitions SET 
                    name = :name, 
                    description = :description, 
                    kpi_type = :kpi_type, 
                    target_value = :target_value, 
                    unit = :unit, 
                    is_active = :is_active,
                    period = :period
                WHERE id = :id";
                
        return $conn->executeStatement($sql, [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'kpi_type' => $data['kpi_type'],
            'target_value' => $data['target_value'] ?? null,
            'unit' => $data['unit'] ?? null,
            'is_active' => (int)($data['is_active'] ?? true),
            'period' => $data['period'] ?? 'day',
        ]) > 0;
    }

    public function deleteKpiDefinition(int $id): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM kpi_definitions WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }

    // --- KPI Results ---
    public function saveKpiResult(array $data): ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO kpi_results (kpi_id, user_id, period_start, period_end, calculated_value, notes) 
                VALUES (:kpi_id, :user_id, :period_start, :period_end, :calculated_value, :notes)
                ON DUPLICATE KEY UPDATE 
                    calculated_value = VALUES(calculated_value),
                    notes = VALUES(notes)";

        $success = $conn->executeStatement($sql, [
            'kpi_id' => $data['kpi_id'],
            'user_id' => $data['user_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'calculated_value' => $data['calculated_value'],
            'notes' => $data['notes'] ?? null,
        ]) > 0;

        if (!$success) {
            return null;
        }

        $lastId = (int)$conn->lastInsertId();
        if ($lastId > 0) {
            return $lastId;
        }

        $sql = "
            SELECT id FROM kpi_results 
            WHERE kpi_id = :kpi_id 
              AND user_id = :user_id 
              AND period_start = :period_start 
              AND period_end = :period_end
            LIMIT 1
        ";
        
        $row = $conn->fetchAssociative($sql, [
            'kpi_id' => $data['kpi_id'],
            'user_id' => $data['user_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
        ]);
        
        return $row ? (int)$row['id'] : null;
    }

    public function findKpiResultsForUser(int $userId, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                kr.*,
                kd.name as kpi_name,
                kd.unit
            FROM kpi_results kr
            JOIN kpi_definitions kd ON kr.kpi_id = kd.id
            WHERE kr.user_id = :user_id
        ";
        $params = ['user_id' => $userId];

        if ($periodStart) {
            $sql .= " AND kr.period_start >= :period_start";
            $params['period_start'] = $periodStart;
        }
        if ($periodEnd) {
            $sql .= " AND kr.period_end <= :period_end";
            $params['period_end'] = $periodEnd;
        }
        $sql .= " ORDER BY kr.period_start DESC";

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function findAllKpiResults(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                kr.*,
                kd.name as kpi_name,
                kd.unit,
                CONCAT(u.last_name, ' ', u.first_name) as user_name
            FROM kpi_results kr
            JOIN kpi_definitions kd ON kr.kpi_id = kd.id
            JOIN users u ON kr.user_id = u.id
            ORDER BY kr.period_start DESC
        ";
        return $conn->fetchAllAssociative($sql);
    }

    public function findLatestKpiResult(int $kpiId, string $periodType): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT kr.*
            FROM kpi_results kr
            WHERE kr.kpi_id = :kpi_id
        ";
        $params = ['kpi_id' => $kpiId];

        switch ($periodType) {
            case 'day':
                $sql .= " AND kr.period_start = kr.period_end"; 
                break;
            case 'week':
                $sql .= " AND DATEDIFF(kr.period_end, kr.period_start) = 6"; 
                break;
            case 'month':
                $sql .= " AND DATEDIFF(kr.period_end, kr.period_start) = 29";
                break;
            default:
                break;
        }

        $sql .= " ORDER BY kr.period_end DESC, kr.id DESC LIMIT 1";

        $result = $conn->fetchAssociative($sql, $params);
        return $result ?: null;
    }

    public function findKpiResultForPreviousPeriod(int $kpiId, string $currentPeriodEnd, string $periodType = 'day'): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT kr.*
            FROM kpi_results kr
            WHERE kr.kpi_id = :kpi_id AND kr.period_end < :current_period_end
            ORDER BY kr.period_end DESC, kr.updated_at DESC, kr.created_at DESC, kr.id DESC
            LIMIT 1
        ";
        
        $result = $conn->fetchAssociative($sql, [
            'kpi_id' => $kpiId,
            'current_period_end' => $currentPeriodEnd
        ]);
        
        return $result ?: null;
    }
}
