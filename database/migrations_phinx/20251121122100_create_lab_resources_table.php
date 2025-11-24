<?php
use Phinx\Migration\AbstractMigration;
class CreateLabResourcesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('lab_resources');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('type', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('capacity', 'integer', ['default' => 1])
              ->addColumn('is_available', 'boolean', ['default' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->create();
    }
}
