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

final class Version20251121122300 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_stock_thresholds_to_inventory_items_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('inventory_items');
        $table->addColumn('min_stock_level', 'integer', ['default' => 0]);
        $table->addColumn('max_stock_level', 'integer', ['default' => 0]);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('inventory_items');
        $table->dropColumn('min_stock_level');
        $table->dropColumn('max_stock_level');
    }
}
