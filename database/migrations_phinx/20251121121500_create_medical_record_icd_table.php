<?php
use Phinx\Migration\AbstractMigration;
class CreateMedicalRecordIcdTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('medical_record_icd', ['id' => false, 'primary_key' => ['medical_record_id', 'icd_code_id']]);
        $table->addColumn('medical_record_id', 'integer')
              ->addColumn('icd_code_id', 'integer')
              ->addForeignKey('medical_record_id', 'medical_records', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('icd_code_id', 'icd_codes', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
