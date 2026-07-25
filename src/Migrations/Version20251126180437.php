<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126180437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_utilization_and_readmission_kpi_types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE kpi_definitions MODIFY COLUMN kpi_type ENUM('appointments_count', 'revenue_generated', 'patient_satisfaction', 'doctor_utilization', 'readmission_rate') NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE kpi_definitions MODIFY COLUMN kpi_type ENUM('appointments_count', 'revenue_generated', 'patient_satisfaction') NOT NULL");
    }
}
