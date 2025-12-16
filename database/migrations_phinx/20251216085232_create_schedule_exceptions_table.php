<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateScheduleExceptionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('schedule_exceptions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('doctor_id', 'integer', ['signed' => false])
              ->addColumn('exception_date', 'date')
              ->addColumn('start_time', 'time')
              ->addColumn('end_time', 'time')
              ->addColumn('is_available', 'boolean')
              ->addColumn('notes', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('doctor_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['doctor_id', 'exception_date'])
              ->create();
    }
}