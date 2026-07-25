<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121122300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_stock_thresholds_to_inventory_items_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('inventory_items');
        $table->addColumn('min_stock_level', 'integer', ['default' => 0]);
        $table->addColumn('max_stock_level', 'integer', ['default' => 0]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('inventory_items');
        $table->dropColumn('min_stock_level');
        $table->dropColumn('max_stock_level');
    }
}
