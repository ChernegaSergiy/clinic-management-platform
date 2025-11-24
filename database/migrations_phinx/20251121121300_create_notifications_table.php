<?php
use Phinx\Migration\AbstractMigration;
class CreateNotificationsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('notifications');
        $table->addColumn('user_id', 'integer')
              ->addColumn('message', 'text')
              ->addColumn('is_read', 'boolean', ['default' => false])
              ->addTimestamps()
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
