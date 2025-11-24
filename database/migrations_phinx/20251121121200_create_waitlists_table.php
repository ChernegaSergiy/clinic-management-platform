<?php
use Phinx\Migration\AbstractMigration;
class CreateWaitlistsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('waitlists', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('patient_id', 'integer', ['signed' => false])
              ->addColumn('desired_doctor_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('desired_start_time', 'datetime', ['null' => true])
              ->addColumn('desired_end_time', 'datetime', ['null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['pending', 'fulfilled', 'cancelled'], 'default' => 'pending'])
              ->addTimestamps()
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('desired_doctor_id', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addIndex(['patient_id'])
              ->addIndex(['desired_doctor_id'])
              ->create();
    }
}
