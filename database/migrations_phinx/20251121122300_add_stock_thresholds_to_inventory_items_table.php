<?php
use Phinx\Migration\AbstractMigration;
class AddStockThresholdsToInventoryItemsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('inventory_items')
            ->addColumn('min_stock_level', 'integer', ['default' => 0, 'after' => 'min_stock_threshold'])
            ->addColumn('max_stock_level', 'integer', ['default' => 0, 'after' => 'min_stock_level'])
            ->save();
    }
}
