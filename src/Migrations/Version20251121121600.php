<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_attachments_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('attachments');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('entity_type', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('filename', 'string', ['length' => 255]);
        $table->addColumn('filepath', 'string', ['length' => 255]);
        $table->addColumn('mime_type', 'string', ['length' => 255]);
        $table->addColumn('size', 'integer');
        $table->addColumn('created_by', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_type', 'entity_id']);
        $table->addIndex(['created_by']);
        $table->addForeignKeyConstraint('users', ['created_by'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('attachments');
    }
}
