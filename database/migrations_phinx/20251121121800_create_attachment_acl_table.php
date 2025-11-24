<?php
use Phinx\Migration\AbstractMigration;
class CreateAttachmentAclTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('attachment_acl', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('attachment_id', 'integer', ['signed' => false])
              ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('role_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('can_view', 'boolean', ['default' => false])
              ->addColumn('can_edit', 'boolean', ['default' => false])
              ->addTimestamps()
              ->addForeignKey('attachment_id', 'attachments', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('role_id', 'roles', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['attachment_id', 'user_id', 'role_id'], ['unique' => true])
              ->addIndex(['attachment_id'])
              ->addIndex(['user_id'])
              ->addIndex(['role_id'])
              ->create();
    }
}
