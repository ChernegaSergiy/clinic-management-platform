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

final class Version20251121120300 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_appointments_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('appointments');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('patient_id', 'integer', ['unsigned' => true]);
        $table->addColumn('doctor_id', 'integer', ['unsigned' => true]);
        $table->addColumn('start_time', 'datetime');
        $table->addColumn('end_time', 'datetime');
        $table->addColumn('status', 'string', ['length' => 50, 'default' => 'scheduled']);
        $table->addColumn('ehealth_episode_id', 'string', ['length' => 36, 'notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['ehealth_episode_id']);
        $table->addIndex(['patient_id']);
        $table->addIndex(['doctor_id']);
        $table->addForeignKeyConstraint('patients', ['patient_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['doctor_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('appointments');
    }
}
