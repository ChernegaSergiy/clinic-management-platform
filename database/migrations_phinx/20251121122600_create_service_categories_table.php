<?php
use Phinx\Migration\AbstractMigration;
class CreateServiceCategoriesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('service_categories');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
