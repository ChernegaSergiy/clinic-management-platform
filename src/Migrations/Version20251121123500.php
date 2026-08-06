<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121123500 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_dictionary_values_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('dictionary_values');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('dictionary_id', 'integer', ['unsigned' => true]);
        $table->addColumn('value', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('order_num', 'integer', ['default' => 0]);
        $table->addColumn('is_active', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['dictionary_id', 'value']);
        $table->addIndex(['dictionary_id']);
        $table->addForeignKeyConstraint('dictionaries', ['dictionary_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('dictionary_values');
    }
}
