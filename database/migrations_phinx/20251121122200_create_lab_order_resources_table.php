<?php
use Phinx\Migration\AbstractMigration;
class CreateLabOrderResourcesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('lab_order_resources', ['id' => false, 'primary_key' => ['lab_order_id', 'lab_resource_id']]);
        $table->addColumn('lab_order_id', 'integer', ['signed' => false])
              ->addColumn('lab_resource_id', 'integer', ['signed' => false])
              ->addForeignKey('lab_order_id', 'lab_orders', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('lab_resource_id', 'lab_resources', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['lab_order_id'])
              ->addIndex(['lab_resource_id'])
              ->create();
    }
}
