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

final class Version20251121121000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_audit_logs_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('audit_logs');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('entity_type', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('user_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('action', 'string', ['length' => 255]);
        $table->addColumn('old_value', 'text', ['notnull' => false]);
        $table->addColumn('new_value', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_type', 'entity_id']);
        $table->addIndex(['user_id']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('audit_logs');
    }
}
