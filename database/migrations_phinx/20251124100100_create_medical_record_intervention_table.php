<?php
use Phinx\Migration\AbstractMigration;
class CreateMedicalRecordInterventionTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('medical_record_intervention', ['id' => false, 'primary_key' => ['medical_record_id', 'intervention_code_id']]);
        $table->addColumn('medical_record_id', 'integer')
              ->addColumn('intervention_code_id', 'integer')
              ->addForeignKey('medical_record_id', 'medical_records', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('intervention_code_id', 'intervention_codes', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
