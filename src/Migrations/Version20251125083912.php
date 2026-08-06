<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251125083912 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'make_password_nullable_in_users_table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NOT NULL');
    }
}
