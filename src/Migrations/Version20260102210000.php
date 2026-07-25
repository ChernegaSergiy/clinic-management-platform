<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260102210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_pending_status_to_patients';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE patients MODIFY COLUMN status ENUM('active', 'archived', 'needs_review', 'pending') DEFAULT 'active'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE patients MODIFY COLUMN status ENUM('active', 'archived', 'needs_review') DEFAULT 'active'");
    }
}
