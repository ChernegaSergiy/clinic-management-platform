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

final class Version20251121120200 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_patients_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('patients');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('first_name', 'string', ['length' => 255]);
        $table->addColumn('last_name', 'string', ['length' => 255]);
        $table->addColumn('middle_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('birth_date', 'date');
        $table->addColumn('gender', 'string', ['length' => 50]);
        $table->addColumn('phone', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('address', 'text', ['notnull' => false]);
        $table->addColumn('tax_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('document_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('ehealth_patient_id', 'string', ['length' => 36, 'notnull' => false]);
        $table->addColumn('active', 'boolean', ['default' => true]);
        $table->addColumn('deceased_date', 'date', ['notnull' => false]);
        $table->addColumn('marital_status', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email']);
        $table->addUniqueIndex(['tax_id']);
        $table->addUniqueIndex(['document_id']);
        $table->addUniqueIndex(['ehealth_patient_id']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('patients');
    }
}
