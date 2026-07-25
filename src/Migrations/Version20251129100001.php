<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_patient_insurance_policies_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('patient_insurance_policies');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('patient_id', 'integer', ['unsigned' => true]);
        $table->addColumn('insurance_company_id', 'integer', ['unsigned' => true]);
        $table->addColumn('policy_number', 'string', ['length' => 255]);
        $table->addColumn('group_number', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('valid_from', 'date');
        $table->addColumn('valid_to', 'date', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('patients', ['patient_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('insurance_companies', ['insurance_company_id'], ['id'], ['onDelete' => 'RESTRICT', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('patient_insurance_policies');
    }
}
