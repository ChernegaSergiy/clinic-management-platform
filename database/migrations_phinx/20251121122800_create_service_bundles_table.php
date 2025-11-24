<?php
use Phinx\Migration\AbstractMigration;
class CreateServiceBundlesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('service_bundles');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
