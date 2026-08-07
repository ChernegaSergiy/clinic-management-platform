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

final class Version20251205173131 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_employees_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('employees');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('first_name', 'string', ['length' => 100]);
        $table->addColumn('last_name', 'string', ['length' => 100]);
        $table->addColumn('middle_name', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('position', 'string', ['length' => 100]);
        $table->addColumn('department', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('hire_date', 'date');
        $table->addColumn('fire_date', 'date', ['notnull' => false]);
        $table->addColumn('salary', 'decimal', ['precision' => 10, 'scale' => 2, 'notnull' => false]);
        $table->addColumn('contact_phone', 'string', ['length' => 20, 'notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 50, 'default' => 'active']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['last_name', 'first_name']);
        $table->addIndex(['status']);
        $table->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('employees');
    }
}
