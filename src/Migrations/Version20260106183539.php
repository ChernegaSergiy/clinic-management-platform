<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106183539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_hotp_support_to_users_table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users MODIFY COLUMN mfa_type ENUM('totp', 'hotp', 'sms', 'email') NOT NULL DEFAULT 'totp'");
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_counter INT NULL DEFAULT 0 AFTER mfa_backup_codes");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');
        $table->dropColumn('mfa_counter');
        $this->addSql("ALTER TABLE users MODIFY COLUMN mfa_type ENUM('totp', 'sms', 'email') NOT NULL DEFAULT 'totp'");
    }
}
