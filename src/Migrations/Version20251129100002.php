<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129100002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_claims_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('claims');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('invoice_id', 'integer', ['unsigned' => true]);
        $table->addColumn('patient_policy_id', 'integer', ['unsigned' => true]);
        $table->addColumn('status', 'string', ['length' => 50, 'default' => 'draft']);
        $table->addColumn('submitted_at', 'datetime', ['notnull' => false]);
        $table->addColumn('total_claimed', 'decimal', ['precision' => 10, 'scale' => 2]);
        $table->addColumn('total_paid', 'decimal', ['precision' => 10, 'scale' => 2, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status']);
        $table->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('patient_insurance_policies', ['patient_policy_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('claims');
    }
}
