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

final class Version20251121122400 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_inventory_movements_table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE inventory_movements (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            inventory_item_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            movement_type ENUM(\'in\', \'out\', \'adjustment\') NOT NULL,
            quantity_change INT NOT NULL,
            new_quantity INT NOT NULL,
            reason TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_inventory_movements_item (inventory_item_id),
            INDEX idx_inventory_movements_user (user_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_inventory_movements_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items (id) ON DELETE CASCADE,
            CONSTRAINT fk_inventory_movements_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE inventory_movements');
    }
}
