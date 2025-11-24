<?php
use Phinx\Migration\AbstractMigration;
class CreateServicesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('services', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('category_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addForeignKey('category_id', 'service_categories', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addIndex(['name'], ['unique' => true])
              ->addIndex(['category_id'])
              ->create();
    }
}
