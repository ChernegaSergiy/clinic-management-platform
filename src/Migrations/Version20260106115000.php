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

final class Version20260106115000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_settings_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('settings');
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('value', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['key']);

        $this->addSql("INSERT INTO settings (`key`, `value`, `created_at`, `updated_at`) VALUES ('mfa_policy', 'optional', NOW(), NOW())");
        $this->addSql("INSERT INTO settings (`key`, `value`, `created_at`, `updated_at`) VALUES ('mfa_force_roles', NULL, NOW(), NOW())");
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('settings');
    }
}
