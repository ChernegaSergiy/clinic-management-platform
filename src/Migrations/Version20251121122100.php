<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121122100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_lab_resources_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('lab_resources');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('capacity', 'integer', ['default' => 1]);
        $table->addColumn('is_available', 'boolean', ['default' => true]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('lab_resources');
    }
}
