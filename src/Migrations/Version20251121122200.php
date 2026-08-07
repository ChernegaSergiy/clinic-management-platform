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

final class Version20251121122200 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_lab_order_resources_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('lab_order_resources');
        $table->addColumn('lab_order_id', 'integer', ['unsigned' => true]);
        $table->addColumn('lab_resource_id', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['lab_order_id', 'lab_resource_id']);
        $table->addIndex(['lab_order_id']);
        $table->addIndex(['lab_resource_id']);
        $table->addForeignKeyConstraint('lab_orders', ['lab_order_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('lab_resources', ['lab_resource_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('lab_order_resources');
    }
}
