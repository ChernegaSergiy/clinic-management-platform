<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260103120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_rooms_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('rooms');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 100]);
        $table->addColumn('capacity', 'integer', ['default' => 1]);
        $table->addColumn('location', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('equipment', 'text', ['notnull' => false]);
        $table->addColumn('is_available', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name']);
        $table->addIndex(['type']);
        $table->addIndex(['is_available']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('rooms');
    }
}
