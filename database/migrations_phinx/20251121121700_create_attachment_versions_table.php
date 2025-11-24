<?php
use Phinx\Migration\AbstractMigration;
class CreateAttachmentVersionsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('attachment_versions', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('attachment_id', 'integer', ['signed' => false])
              ->addColumn('version_number', 'integer', ['default' => 1])
              ->addColumn('filepath', 'string', ['limit' => 255])
              ->addColumn('filename', 'string', ['limit' => 255])
              ->addColumn('size', 'integer')
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addTimestamps()
              ->addForeignKey('attachment_id', 'attachments', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('created_by', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addIndex(['attachment_id', 'version_number'], ['unique' => true])
              ->addIndex(['attachment_id'])
              ->addIndex(['created_by'])
              ->create();
    }
}
