<?php
use Phinx\Migration\AbstractMigration;
class CreateDictionaryValuesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('dictionary_values', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('dictionary_id', 'integer', ['signed' => false])
              ->addColumn('value', 'string', ['limit' => 255])
              ->addColumn('label', 'string', ['limit' => 255])
              ->addColumn('order_num', 'integer', ['default' => 0])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addTimestamps()
              ->addForeignKey('dictionary_id', 'dictionaries', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['dictionary_id', 'value'], ['unique' => true])
              ->addIndex(['dictionary_id'])
              ->create();
    }
}
