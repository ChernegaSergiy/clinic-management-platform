<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDoctorSchedulesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('doctor_schedules', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('doctor_id', 'integer', ['signed' => false])
              ->addColumn('day_of_week', 'integer', ['comment' => '0 = Sunday, 1 = Monday, ..., 6 = Saturday'])
              ->addColumn('start_time', 'time')
              ->addColumn('end_time', 'time')
              ->addColumn('is_available', 'boolean', ['default' => true])
              ->addTimestamps()
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['doctor_id', 'day_of_week'], ['unique' => true])
              ->create();
    }
}