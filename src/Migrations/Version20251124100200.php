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

final class Version20251124100200 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_ticket_and_contact_to_waitlists_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('waitlists');
        $table->addColumn('ticket_number', 'string', ['length' => 32, 'notnull' => false]);
        $table->addColumn('contact_phone', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('contact_email', 'string', ['length' => 191, 'notnull' => false]);
        $table->addUniqueIndex(['ticket_number'], 'uniq_waitlists_ticket_number');
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('waitlists');
        $table->dropIndex('uniq_waitlists_ticket_number');
        $table->dropColumn('contact_email');
        $table->dropColumn('contact_phone');
        $table->dropColumn('ticket_number');
    }
}
