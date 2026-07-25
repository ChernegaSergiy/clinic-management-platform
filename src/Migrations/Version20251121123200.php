<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121123200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_kpi_definitions_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kpi_definitions');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('kpi_type', 'string', ['length' => 50]);
        $table->addColumn('target_value', 'decimal', ['precision' => 10, 'scale' => 2, 'notnull' => false]);
        $table->addColumn('unit', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('is_active', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kpi_definitions');
    }
}
