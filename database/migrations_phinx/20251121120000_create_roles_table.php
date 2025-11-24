<?php

use Phinx\Migration\AbstractMigration;

class CreateRolesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('roles');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addTimestamps()
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
