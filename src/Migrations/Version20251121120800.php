<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_invoices_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('invoices');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('patient_id', 'integer', ['unsigned' => true]);
        $table->addColumn('appointment_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('medical_record_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2]);
        $table->addColumn('status', 'string', ['length' => 50, 'default' => 'pending']);
        $table->addColumn('issued_date', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('paid_date', 'datetime', ['notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['patient_id']);
        $table->addIndex(['appointment_id']);
        $table->addIndex(['medical_record_id']);
        $table->addForeignKeyConstraint('patients', ['patient_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('appointments', ['appointment_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('medical_records', ['medical_record_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('invoices');
    }
}
