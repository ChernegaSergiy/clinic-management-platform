<?php
use Phinx\Migration\AbstractMigration;
class CreateMedicalRecordsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('medical_records', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('patient_id', 'integer', ['signed' => false])
              ->addColumn('appointment_id', 'integer', ['signed' => false])
              ->addColumn('doctor_id', 'integer', ['signed' => false])
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
              ->addIndex(['patient_id'])
              ->addIndex(['appointment_id'])
              ->addIndex(['doctor_id'])
              ->create();
    }
}
