<?php

use Phinx\Migration\AbstractMigration;

class AddTicketAndContactToWaitlistsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('waitlists');
        $needsUpdate = false;

        if (!$table->hasColumn('ticket_number')) {
            $table->addColumn('ticket_number', 'string', [
                'limit' => 32,
                'null' => true,
                'after' => 'id',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('contact_phone')) {
            $table->addColumn('contact_phone', 'string', [
                'limit' => 50,
                'null' => true,
                'after' => 'notes',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('contact_email')) {
            $table->addColumn('contact_email', 'string', [
                'limit' => 191,
                'null' => true,
                'after' => 'contact_phone',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasIndex(['ticket_number'])) {
            $table->addIndex(['ticket_number'], ['unique' => true]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }
    }
}
