<?php
use Phinx\Migration\AbstractMigration;
class CreateMedicalRecordsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('medical_records');
        $table->addColumn('patient_id', 'integer')
              ->addColumn('appointment_id', 'integer')
              ->addColumn('doctor_id', 'integer')
              ->addColumn('visit_date', 'datetime')
              ->addColumn('diagnosis_code', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('diagnosis_text', 'text', ['null' => true])
              ->addColumn('treatment', 'text', ['null' => true])
              ->addColumn('ehealth_record_id', 'string', ['limit' => 36, 'null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('appointment_id', 'appointments', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['ehealth_record_id'], ['unique' => true])
              ->create();
    }
}
