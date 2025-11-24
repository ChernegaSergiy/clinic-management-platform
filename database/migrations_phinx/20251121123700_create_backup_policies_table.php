<?php
use Phinx\Migration\AbstractMigration;
class CreateBackupPoliciesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('backup_policies');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('frequency', 'enum', ['values' => ['daily', 'weekly', 'monthly'], 'default' => 'daily'])
              ->addColumn('retention_days', 'integer', ['default' => 30])
              ->addColumn('last_run_at', 'datetime', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['active', 'inactive', 'failed'], 'default' => 'inactive'])
              ->addTimestamps()
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
