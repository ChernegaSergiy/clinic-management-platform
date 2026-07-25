<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121123700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_backup_policies_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('backup_policies');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('frequency', 'string', ['length' => 20, 'default' => 'daily']);
        $table->addColumn('retention_days', 'integer', ['default' => 30]);
        $table->addColumn('last_run_at', 'datetime', ['notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 20, 'default' => 'inactive']);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('backup_policies');
    }
}
