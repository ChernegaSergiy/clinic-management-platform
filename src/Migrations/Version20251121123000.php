<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_payments_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('payments');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('invoice_id', 'integer', ['unsigned' => true]);
        $table->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2]);
        $table->addColumn('payment_method', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('transaction_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('payment_date', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['invoice_id']);
        $table->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('payments');
    }
}
