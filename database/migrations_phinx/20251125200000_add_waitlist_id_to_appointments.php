<?php

use Phinx\Migration\AbstractMigration;

class AddWaitlistIdToAppointments extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('appointments');
        $table->addColumn('waitlist_id', 'integer', [
            'null' => true,
            'after' => 'patient_id', // Place it logically after patient_id
            'signed' => false, // Assuming IDs are always positive
        ])
        ->addForeignKey('waitlist_id', 'waitlists', 'id', ['delete' => 'SET NULL', 'update' => 'CASCADE'])
        ->update();
    }
}
