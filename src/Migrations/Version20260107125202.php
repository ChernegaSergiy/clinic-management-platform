<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107125202 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_hotp_last_counter_to_users_table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE users ADD COLUMN mfa_last_counter INT NULL DEFAULT NULL AFTER mfa_counter");
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('users');
        $table->dropColumn('mfa_last_counter');
    }
}
