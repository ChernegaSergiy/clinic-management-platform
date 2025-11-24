<?php
use Phinx\Migration\AbstractMigration;
class CreateLabOrdersTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('lab_orders');
        $table->addColumn('patient_id', 'integer')
              ->addColumn('doctor_id', 'integer')
              ->addColumn('medical_record_id', 'integer')
              ->addColumn('order_code', 'string', ['limit' => 255])
              ->addColumn('status', 'enum', ['values' => ['ordered', 'in_progress', 'completed', 'cancelled'], 'default' => 'ordered'])
              ->addColumn('qr_code_hash', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('results', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('medical_record_id', 'medical_records', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['qr_code_hash'], ['unique' => true])
              ->create();
    }
}
