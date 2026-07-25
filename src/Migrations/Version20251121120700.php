<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_inventory_items_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('inventory_items');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('inn', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('batch_number', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('expiry_date', 'date', ['notnull' => false]);
        $table->addColumn('supplier', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('cost', 'decimal', ['precision' => 10, 'scale' => 2, 'notnull' => false]);
        $table->addColumn('quantity', 'integer', ['default' => 0]);
        $table->addColumn('min_stock_threshold', 'integer', ['default' => 0]);
        $table->addColumn('location', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('inventory_items');
    }
}
