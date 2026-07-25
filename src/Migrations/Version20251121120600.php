<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_lab_orders_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('lab_orders');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('patient_id', 'integer', ['unsigned' => true]);
        $table->addColumn('doctor_id', 'integer', ['unsigned' => true]);
        $table->addColumn('medical_record_id', 'integer', ['unsigned' => true]);
        $table->addColumn('order_code', 'string', ['length' => 255]);
        $table->addColumn('status', 'string', ['length' => 50, 'default' => 'ordered']);
        $table->addColumn('qr_code_hash', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('results', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['qr_code_hash']);
        $table->addIndex(['patient_id']);
        $table->addIndex(['doctor_id']);
        $table->addIndex(['medical_record_id']);
        $table->addForeignKeyConstraint('patients', ['patient_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['doctor_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('medical_records', ['medical_record_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('lab_orders');
    }
}
