<?php

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
