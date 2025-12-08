<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNewsArticlesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * For more information on using tables please check:
     * https://book.cakephp.org/phinx/0/en/migrations.html#working-with-tables
     */
    public function change(): void
    {
        $table = $this->table('news_articles');
        $table->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('meta', 'string', ['limit' => 500, 'comment' => 'Short description or summary'])
              ->addColumn('content', 'text', ['comment' => 'Full article HTML content'])
              ->addColumn('published_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('author_id', 'integer', ['null' => true, 'comment' => 'Foreign key to users table', 'signed' => false])
              ->addForeignKey('author_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addColumn('is_published', 'boolean', ['default' => true])
              ->addTimestamps() // Adds created_at and updated_at
              ->create();
    }
}
