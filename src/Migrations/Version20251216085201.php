<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251216085201 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_duration_to_services_table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE services ADD COLUMN duration_minutes INT NOT NULL DEFAULT 30 AFTER price");
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('services');
        $table->dropColumn('duration_minutes');
    }
}
