<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121122700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_services_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('services');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2]);
        $table->addColumn('category_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('is_active', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
        $table->addIndex(['category_id']);
        $table->addForeignKeyConstraint('service_categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('services');
    }
}
