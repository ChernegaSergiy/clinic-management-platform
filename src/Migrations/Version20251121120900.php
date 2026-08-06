<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120900 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_status_to_patients_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('patients');
        $table->addColumn('status', 'string', ['length' => 50, 'default' => 'active']);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('patients');
        $table->dropColumn('status');
    }
}
