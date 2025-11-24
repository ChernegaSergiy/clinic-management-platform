<?php
use Phinx\Migration\AbstractMigration;
class CreateAttachmentVersionsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('attachment_versions');
        $table->addColumn('attachment_id', 'integer')
              ->addColumn('version_number', 'integer', ['default' => 1])
              ->addColumn('filepath', 'string', ['limit' => 255])
              ->addColumn('filename', 'string', ['limit' => 255])
              ->addColumn('size', 'integer')
              ->addColumn('created_by', 'integer', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('attachment_id', 'attachments', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('created_by', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addIndex(['attachment_id', 'version_number'], ['unique' => true])
              ->create();
    }
}
