<?php
use Phinx\Migration\AbstractMigration;
class CreateAttachmentAclTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('attachment_acl');
        $table->addColumn('attachment_id', 'integer')
              ->addColumn('user_id', 'integer', ['null' => true])
              ->addColumn('role_id', 'integer', ['null' => true])
              ->addColumn('can_view', 'boolean', ['default' => false])
              ->addColumn('can_edit', 'boolean', ['default' => false])
              ->addTimestamps()
              ->addForeignKey('attachment_id', 'attachments', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('role_id', 'roles', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['attachment_id', 'user_id', 'role_id'], ['unique' => true])
              ->create();
    }
}
