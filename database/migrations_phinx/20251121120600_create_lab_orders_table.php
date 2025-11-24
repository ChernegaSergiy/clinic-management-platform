<?php
use Phinx\Migration\AbstractMigration;
class CreateLabOrdersTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('lab_orders', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('patient_id', 'integer', ['signed' => false])
              ->addColumn('doctor_id', 'integer', ['signed' => false])
              ->addColumn('medical_record_id', 'integer', ['signed' => false])
              ->addColumn('order_code', 'string', ['limit' => 255])
              ->addColumn('status', 'enum', ['values' => ['ordered', 'in_progress', 'completed', 'cancelled'], 'default' => 'ordered'])
              ->addColumn('qr_code_hash', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('results', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('medical_record_id', 'medical_records', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['qr_code_hash'], ['unique' => true])
              ->addIndex(['patient_id'])
              ->addIndex(['doctor_id'])
              ->addIndex(['medical_record_id'])
              ->create();
    }
}
