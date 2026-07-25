<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_attachment_acl_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('attachment_acl');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('attachment_id', 'integer', ['unsigned' => true]);
        $table->addColumn('user_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('role_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('can_view', 'boolean', ['default' => false]);
        $table->addColumn('can_edit', 'boolean', ['default' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['attachment_id', 'user_id', 'role_id']);
        $table->addIndex(['attachment_id']);
        $table->addIndex(['user_id']);
        $table->addIndex(['role_id']);
        $table->addForeignKeyConstraint('attachments', ['attachment_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('roles', ['role_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('attachment_acl');
    }
}
