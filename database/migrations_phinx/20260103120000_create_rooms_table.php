<?php

use Phinx\Migration\AbstractMigration;

class CreateRoomsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('rooms');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('type', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('capacity', 'integer', ['default' => 1])
              ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('equipment', 'text', ['null' => true])
              ->addColumn('is_available', 'boolean', ['default' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['name'])
              ->addIndex(['type'])
              ->addIndex(['is_available'])
              ->create();
    }
}
