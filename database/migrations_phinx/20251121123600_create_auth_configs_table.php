<?php
use Phinx\Migration\AbstractMigration;
class CreateAuthConfigsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('auth_configs');
        $table->addColumn('provider', 'string', ['limit' => 255])
              ->addColumn('client_id', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('client_secret', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('is_active', 'boolean', ['default' => false])
              ->addColumn('config', 'text', ['null' => true])
              ->addTimestamps()
              ->addIndex(['provider'], ['unique' => true])
              ->create();
    }
}
