<?php
use Phinx\Migration\AbstractMigration;
class CreateInventoryMovementsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('inventory_movements', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('inventory_item_id', 'integer', ['signed' => false])
              ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('movement_type', 'enum', ['values' => ['in', 'out', 'adjustment']])
              ->addColumn('quantity_change', 'integer')
              ->addColumn('new_quantity', 'integer')
              ->addColumn('reason', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('inventory_item_id', 'inventory_items', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addIndex(['inventory_item_id'])
              ->addIndex(['user_id'])
              ->create();
    }
}
