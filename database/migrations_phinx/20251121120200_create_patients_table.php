<?php
use Phinx\Migration\AbstractMigration;
class CreatePatientsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('patients', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('first_name', 'string', ['limit' => 255])
              ->addColumn('last_name', 'string', ['limit' => 255])
              ->addColumn('middle_name', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('birth_date', 'date')
              ->addColumn('gender', 'enum', ['values' => ['male', 'female', 'other', 'unknown']])
              ->addColumn('phone', 'string', ['limit' => 255])
              ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('address', 'text', ['null' => true])
              ->addColumn('tax_id', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('document_id', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('ehealth_patient_id', 'string', ['limit' => 36, 'null' => true])
              ->addColumn('active', 'boolean', ['default' => true])
              ->addColumn('deceased_date', 'date', ['null' => true])
              ->addColumn('marital_status', 'enum', ['values' => ['single', 'married', 'divorced', 'widowed', 'unknown'], 'null' => true])
              ->addTimestamps()
              ->addIndex(['email'], ['unique' => true])
              ->addIndex(['tax_id'], ['unique' => true])
              ->addIndex(['document_id'], ['unique' => true])
              ->addIndex(['ehealth_patient_id'], ['unique' => true])
              ->create();
    }
}
