<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121122600 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_service_categories_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('service_categories');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('service_categories');
    }
}
