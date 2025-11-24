<?php
use Phinx\Migration\AbstractMigration;
class CreateNotificationsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('notifications', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('user_id', 'integer', ['signed' => false])
              ->addColumn('message', 'text')
              ->addColumn('is_read', 'boolean', ['default' => false])
              ->addTimestamps()
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['user_id'])
              ->create();
    }
}
