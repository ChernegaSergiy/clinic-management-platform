<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260809081302 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE roles SET name = CONCAT('ROLE_', UPPER(name)) WHERE name NOT LIKE 'ROLE_%'");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("UPDATE roles SET name = LOWER(REPLACE(name, 'ROLE_', '')) WHERE name LIKE 'ROLE_%'");
    }
}
