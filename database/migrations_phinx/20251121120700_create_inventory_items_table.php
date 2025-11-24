<?php
use Phinx\Migration\AbstractMigration;
class CreateInventoryItemsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('inventory_items');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('inn', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('batch_number', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('expiry_date', 'date', ['null' => true])
              ->addColumn('supplier', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('cost', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('quantity', 'integer', ['default' => 0])
              ->addColumn('min_stock_threshold', 'integer', ['default' => 0])
              ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
              ->addTimestamps()
              ->create();
    }
}
