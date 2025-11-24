<?php
use Phinx\Migration\AbstractMigration;
class CreatePrescriptionsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('prescriptions', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('patient_id', 'integer', ['signed' => false])
              ->addColumn('doctor_id', 'integer', ['signed' => false])
              ->addColumn('medical_record_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('issue_date', 'date')
              ->addColumn('expiry_date', 'date', ['null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('medical_record_id', 'medical_records', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addIndex(['patient_id'])
              ->addIndex(['doctor_id'])
              ->addIndex(['medical_record_id'])
              ->create();
    }
}
