<?php
use Phinx\Migration\AbstractMigration;
class CreateInventoryMovementsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('inventory_movements');
        $table->addColumn('inventory_item_id', 'integer')
              ->addColumn('user_id', 'integer', ['null' => true])
              ->addColumn('movement_type', 'enum', ['values' => ['in', 'out', 'adjustment']])
              ->addColumn('quantity_change', 'integer')
              ->addColumn('new_quantity', 'integer')
              ->addColumn('reason', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('inventory_item_id', 'inventory_items', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
