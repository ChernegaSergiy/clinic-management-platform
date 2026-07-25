<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_mfa_policy_settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE settings ADD COLUMN mfa_policy ENUM('optional', 'admin_required', 'all_required', 'disabled') NOT NULL DEFAULT 'optional'");
        $this->addSql("ALTER TABLE settings ADD COLUMN mfa_force_roles JSON NULL");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('settings');
        $table->dropColumn('mfa_force_roles');
        $table->dropColumn('mfa_policy');
    }
}
