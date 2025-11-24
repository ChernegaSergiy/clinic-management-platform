<?php
use Phinx\Migration\AbstractMigration;
class CreateContractsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('contracts');
        $table->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('start_date', 'date')
              ->addColumn('end_date', 'date', ['null' => true])
              ->addColumn('party_a', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('party_b', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('file_path', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('status', 'enum', ['values' => ['active', 'expired', 'terminated'], 'default' => 'active'])
              ->addTimestamps()
              ->create();
    }
}
