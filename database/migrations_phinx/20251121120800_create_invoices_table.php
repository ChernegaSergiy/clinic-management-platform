<?php
use Phinx\Migration\AbstractMigration;
class CreateInvoicesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('invoices', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('patient_id', 'integer', ['signed' => false])
              ->addColumn('appointment_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('medical_record_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('status', 'enum', ['values' => ['pending', 'paid', 'cancelled'], 'default' => 'pending'])
              ->addColumn('issued_date', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('paid_date', 'datetime', ['null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('appointment_id', 'appointments', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addForeignKey('medical_record_id', 'medical_records', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addIndex(['patient_id'])
              ->addIndex(['appointment_id'])
              ->addIndex(['medical_record_id'])
              ->create();
    }
}
