<?php
use Phinx\Migration\AbstractMigration;
class CreateAppointmentsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('appointments');
        $table->addColumn('patient_id', 'integer', ['signed' => false])
              ->addColumn('doctor_id', 'integer', ['signed' => false])
              ->addColumn('start_time', 'datetime')
              ->addColumn('end_time', 'datetime')
              ->addColumn('status', 'enum', ['values' => ['scheduled', 'completed', 'cancelled', 'no-show'], 'default' => 'scheduled'])
              ->addColumn('ehealth_episode_id', 'string', ['limit' => 36, 'null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('patient_id', 'patients', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['ehealth_episode_id'], ['unique' => true])
              ->addIndex(['patient_id'])
              ->addIndex(['doctor_id'])
              ->create();
    }
}
