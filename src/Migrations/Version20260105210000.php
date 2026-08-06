<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260105210000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_departments_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('departments');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('parent_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('is_active', 'boolean', ['default' => true]);
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
        $table->addIndex(['is_active']);
        $table->addIndex(['sort_order']);
        $table->addForeignKeyConstraint('departments', ['parent_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('departments');
    }
}
