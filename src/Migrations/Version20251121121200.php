<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_waitlists_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('waitlists');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('patient_id', 'integer', ['unsigned' => true]);
        $table->addColumn('desired_doctor_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('desired_start_time', 'datetime', ['notnull' => false]);
        $table->addColumn('desired_end_time', 'datetime', ['notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 50, 'default' => 'pending']);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['patient_id']);
        $table->addIndex(['desired_doctor_id']);
        $table->addForeignKeyConstraint('patients', ['patient_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['desired_doctor_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('waitlists');
    }
}
