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

final class Version20251121121800 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_attachment_acl_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('attachment_acl');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('attachment_id', 'integer', ['unsigned' => true]);
        $table->addColumn('user_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('role_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('can_view', 'boolean', ['default' => false]);
        $table->addColumn('can_edit', 'boolean', ['default' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['attachment_id', 'user_id', 'role_id']);
        $table->addIndex(['attachment_id']);
        $table->addIndex(['user_id']);
        $table->addIndex(['role_id']);
        $table->addForeignKeyConstraint('attachments', ['attachment_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('roles', ['role_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('attachment_acl');
    }
}
