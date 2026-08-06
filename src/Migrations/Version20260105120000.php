<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260105120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_mfa_settings_to_users_table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash");
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_type ENUM('totp', 'sms', 'email') NOT NULL DEFAULT 'totp' AFTER mfa_enabled");
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_secret VARCHAR(255) NULL AFTER mfa_type");
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_backup_codes JSON NULL AFTER mfa_secret");
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_verified_at DATETIME NULL AFTER mfa_backup_codes");
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_pending TINYINT(1) NOT NULL DEFAULT 0 AFTER mfa_verified_at");
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('users');
        $table->dropColumn('mfa_pending');
        $table->dropColumn('mfa_verified_at');
        $table->dropColumn('mfa_backup_codes');
        $table->dropColumn('mfa_secret');
        $table->dropColumn('mfa_type');
        $table->dropColumn('mfa_enabled');
    }
}
