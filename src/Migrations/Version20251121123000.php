<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121123000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_payments_table';
    }

    public function up(Schema $schema) : void
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

    public function down(Schema $schema) : void
    {
        $schema->dropTable('payments');
    }
}
