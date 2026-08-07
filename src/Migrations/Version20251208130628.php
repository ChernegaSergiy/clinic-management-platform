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

final class Version20251208130628 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_news_articles_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('news_articles');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('meta', 'string', ['length' => 500]);
        $table->addColumn('content', 'text');
        $table->addColumn('published_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('author_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('is_published', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('users', ['author_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'CASCADE']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('news_articles');
    }
}
