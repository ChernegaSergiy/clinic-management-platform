<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121123100 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_contracts_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('contracts');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('start_date', 'date');
        $table->addColumn('end_date', 'date', ['notnull' => false]);
        $table->addColumn('party_a', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('party_b', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('file_path', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 20, 'default' => 'active']);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('contracts');
    }
}
