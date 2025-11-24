<?php
use Phinx\Migration\AbstractMigration;
class CreatePrescriptionsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('prescriptions');
        $table->addColumn('patient_id', 'integer')
              ->addColumn('doctor_id', 'integer')
              ->addColumn('medical_record_id', 'integer', ['null' => true])
              ->addColumn('issue_date', 'date')
              ->addColumn('expiry_date', 'date', ['null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('medical_record_id', 'medical_records', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
