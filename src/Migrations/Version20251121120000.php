<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_roles_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('roles');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('roles');
    }
}
