<?php

use Phinx\Migration\AbstractMigration;

class AddRoomIdToAppointmentsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('appointments');
        $table->addColumn('room_id', 'integer', ['signed' => false, 'null' => true])
              ->addIndex(['room_id'])
              ->addForeignKey('room_id', 'rooms', 'id', ['delete' => 'SET NULL', 'update' => 'NO_ACTION'])
              ->update();
    }
}
