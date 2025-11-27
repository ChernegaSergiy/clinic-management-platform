<?php

namespace App\Module\Admin\Repository;

use App\Database;
use PDO;

class KpiRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // --- KPI Definitions ---
    public function findAllKpiDefinitions(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM kpi_definitions ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActiveKpiDefinitions(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM kpi_definitions WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findKpiDefinitionById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM kpi_definitions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function saveKpiDefinition(array $data): ?int
    {
        $sql = "INSERT INTO kpi_definitions (name, description, kpi_type, target_value, unit, is_active, period) 
                VALUES (:name, :description, :kpi_type, :target_value, :unit, :is_active, :period)";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':kpi_type' => $data['kpi_type'],
            ':target_value' => $data['target_value'] ?? null,
            ':unit' => $data['unit'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
            ':period' => $data['period'] ?? 'day',
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : null;
    }

    public function updateKpiDefinition(int $id, array $data): bool
    {
        $sql = "UPDATE kpi_definitions SET 
                    name = :name, 
                    description = :description, 
                    kpi_type = :kpi_type, 
                    target_value = :target_value, 
                    unit = :unit, 
                    is_active = :is_active,
                    period = :period
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':kpi_type' => $data['kpi_type'],
            ':target_value' => $data['target_value'] ?? null,
            ':unit' => $data['unit'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
            ':period' => $data['period'] ?? 'day',
        ]);
    }

    public function deleteKpiDefinition(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM kpi_definitions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // --- KPI Results ---
    public function saveKpiResult(array $data): ?int
    {
        $sql = "INSERT INTO kpi_results (kpi_id, user_id, period_start, period_end, calculated_value, notes) 
                VALUES (:kpi_id, :user_id, :period_start, :period_end, :calculated_value, :notes)
                ON DUPLICATE KEY UPDATE 
                    calculated_value = VALUES(calculated_value),
                    notes = VALUES(notes)";

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':kpi_id' => $data['kpi_id'],
            ':user_id' => $data['user_id'],
            ':period_start' => $data['period_start'],
            ':period_end' => $data['period_end'],
            ':calculated_value' => $data['calculated_value'],
            ':notes' => $data['notes'] ?? null,
        ]);

        if (!$success) {
            return null;
        }

        // If a duplicate was updated, lastInsertId will be 0; fetch existing id for consistency
        $lastId = (int)$this->pdo->lastInsertId();
        if ($lastId > 0) {
            return $lastId;
        }

        $stmt = $this->pdo->prepare("
            SELECT id FROM kpi_results 
            WHERE kpi_id = :kpi_id 
              AND user_id = :user_id 
              AND period_start = :period_start 
              AND period_end = :period_end
            LIMIT 1
        ");
        $stmt->execute([
            ':kpi_id' => $data['kpi_id'],
            ':user_id' => $data['user_id'],
            ':period_start' => $data['period_start'],
            ':period_end' => $data['period_end'],
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    public function findKpiResultsForUser(int $userId, string $periodStart = null, string $periodEnd = null): array
    {
        $sql = "
            SELECT 
                kr.*,
                kd.name as kpi_name,
                kd.unit
            FROM kpi_results kr
            JOIN kpi_definitions kd ON kr.kpi_id = kd.id
            WHERE kr.user_id = :user_id
        ";
        $params = [':user_id' => $userId];

        if ($periodStart) {
            $sql .= " AND kr.period_start >= :period_start";
            $params[':period_start'] = $periodStart;
        }
        if ($periodEnd) {
            $sql .= " AND kr.period_end <= :period_end";
            $params[':period_end'] = $periodEnd;
        }
        $sql .= " ORDER BY kr.period_start DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllKpiResults(): array
    {
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
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findLatestKpiResult(int $kpiId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT kr.*
            FROM kpi_results kr
            WHERE kr.kpi_id = :kpi_id
            ORDER BY kr.period_start DESC, kr.updated_at DESC, kr.created_at DESC, kr.id DESC
            LIMIT 1
        ");
        $stmt->execute([':kpi_id' => $kpiId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function findKpiResultForPreviousPeriod(int $kpiId, string $currentPeriodEnd, string $periodType = 'day'): ?array
    {
        // Adjust currentPeriodEnd to exclude the current period
        $stmt = $this->pdo->prepare("
            SELECT kr.*
            FROM kpi_results kr
            WHERE kr.kpi_id = :kpi_id AND kr.period_end < :current_period_end
            ORDER BY kr.period_end DESC, kr.updated_at DESC, kr.created_at DESC, kr.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':kpi_id' => $kpiId,
            ':current_period_end' => $currentPeriodEnd
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }
}
