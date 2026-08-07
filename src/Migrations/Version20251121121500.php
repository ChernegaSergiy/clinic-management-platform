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

final class Version20251121121500 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_medical_record_icd_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('medical_record_icd');
        $table->addColumn('medical_record_id', 'integer', ['unsigned' => true]);
        $table->addColumn('icd_code_id', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['medical_record_id', 'icd_code_id']);
        $table->addIndex(['medical_record_id']);
        $table->addIndex(['icd_code_id']);
        $table->addForeignKeyConstraint('medical_records', ['medical_record_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('icd_codes', ['icd_code_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('medical_record_icd');
    }
}
