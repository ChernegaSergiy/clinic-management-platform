<?php
use Phinx\Migration\AbstractMigration;
class AddDefaultStatusToAppointments extends AbstractMigration
{
    public function up()
    {
        $this->table('appointments')
            ->changeColumn('status', 'enum', ['values' => ['scheduled', 'completed', 'cancelled', 'no-show'], 'default' => 'scheduled'])
            ->save();
    }

    public function down()
    {
        $this->table('appointments')
            ->changeColumn('status', 'enum', ['values' => ['scheduled', 'completed', 'cancelled', 'no-show']])
            ->save();
    }
}
