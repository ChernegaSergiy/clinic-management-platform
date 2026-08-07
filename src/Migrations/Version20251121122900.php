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

final class Version20251121122900 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_bundle_services_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('bundle_services');
        $table->addColumn('bundle_id', 'integer', ['unsigned' => true]);
        $table->addColumn('service_id', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['bundle_id', 'service_id']);
        $table->addIndex(['bundle_id']);
        $table->addIndex(['service_id']);
        $table->addForeignKeyConstraint('service_bundles', ['bundle_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('services', ['service_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('bundle_services');
    }
}
