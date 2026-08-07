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

final class Version20251121121900 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_prescriptions_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('prescriptions');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('patient_id', 'integer', ['unsigned' => true]);
        $table->addColumn('doctor_id', 'integer', ['unsigned' => true]);
        $table->addColumn('medical_record_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('issue_date', 'date');
        $table->addColumn('expiry_date', 'date', ['notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['patient_id']);
        $table->addIndex(['doctor_id']);
        $table->addIndex(['medical_record_id']);
        $table->addForeignKeyConstraint('patients', ['patient_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['doctor_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('medical_records', ['medical_record_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('prescriptions');
    }
}
