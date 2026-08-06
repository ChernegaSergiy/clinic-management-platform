<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121700 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_attachment_versions_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('attachment_versions');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('attachment_id', 'integer', ['unsigned' => true]);
        $table->addColumn('version_number', 'integer', ['default' => 1]);
        $table->addColumn('filepath', 'string', ['length' => 255]);
        $table->addColumn('filename', 'string', ['length' => 255]);
        $table->addColumn('size', 'integer');
        $table->addColumn('created_by', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['attachment_id', 'version_number']);
        $table->addIndex(['attachment_id']);
        $table->addIndex(['created_by']);
        $table->addForeignKeyConstraint('attachments', ['attachment_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['created_by'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('attachment_versions');
    }
}
