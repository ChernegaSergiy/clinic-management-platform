<?php
use Phinx\Migration\AbstractMigration;
class CreateAuditLogsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('audit_logs');
        $table->addColumn('entity_type', 'string', ['limit' => 255])
              ->addColumn('entity_id', 'integer')
              ->addColumn('user_id', 'integer', ['null' => true])
              ->addColumn('action', 'string', ['limit' => 255])
              ->addColumn('old_value', 'text', ['null' => true])
              ->addColumn('new_value', 'text', ['null' => true])
              ->addTimestamps()
              ->create();
    }
}
