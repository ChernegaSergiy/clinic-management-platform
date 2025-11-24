<?php
use Phinx\Migration\AbstractMigration;
class CreateServiceCategoriesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('service_categories', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
