<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121122000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_prescription_items_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('prescription_items');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('prescription_id', 'integer', ['unsigned' => true]);
        $table->addColumn('medication_name', 'string', ['length' => 255]);
        $table->addColumn('dosage', 'string', ['length' => 255]);
        $table->addColumn('frequency', 'string', ['length' => 255]);
        $table->addColumn('duration', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['prescription_id']);
        $table->addForeignKeyConstraint('prescriptions', ['prescription_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('prescription_items');
    }
}
