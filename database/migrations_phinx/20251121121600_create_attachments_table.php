<?php
use Phinx\Migration\AbstractMigration;
class CreateAttachmentsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('attachments');
        $table->addColumn('entity_type', 'string', ['limit' => 255])
              ->addColumn('entity_id', 'integer')
              ->addColumn('filename', 'string', ['limit' => 255])
              ->addColumn('filepath', 'string', ['limit' => 255])
              ->addColumn('mime_type', 'string', ['limit' => 255])
              ->addColumn('size', 'integer')
              ->addColumn('created_by', 'integer', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('created_by', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
