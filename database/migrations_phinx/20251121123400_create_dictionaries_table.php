<?php
use Phinx\Migration\AbstractMigration;
class CreateDictionariesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('dictionaries');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('type', 'string', ['limit' => 255, 'null' => true])
              ->addTimestamps()
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
