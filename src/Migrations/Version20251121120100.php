<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120100 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_users_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('users');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('username', 'string', ['length' => 255]);
        $table->addColumn('password_hash', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('first_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('last_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('role_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['username']);
        $table->addUniqueIndex(['email']);
        $table->addIndex(['role_id']);
        $table->addForeignKeyConstraint('roles', ['role_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('users');
    }
}
