<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_medical_records_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('medical_records');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('patient_id', 'integer', ['unsigned' => true]);
        $table->addColumn('appointment_id', 'integer', ['unsigned' => true]);
        $table->addColumn('doctor_id', 'integer', ['unsigned' => true]);
        $table->addColumn('visit_date', 'datetime');
        $table->addColumn('diagnosis_code', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('diagnosis_text', 'text', ['notnull' => false]);
        $table->addColumn('treatment', 'text', ['notnull' => false]);
        $table->addColumn('ehealth_record_id', 'string', ['length' => 36, 'notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['ehealth_record_id']);
        $table->addIndex(['patient_id']);
        $table->addIndex(['appointment_id']);
        $table->addIndex(['doctor_id']);
        $table->addForeignKeyConstraint('patients', ['patient_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('appointments', ['appointment_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['doctor_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('medical_records');
    }
}
