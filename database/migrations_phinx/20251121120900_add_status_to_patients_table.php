<?php
use Phinx\Migration\AbstractMigration;
class AddStatusToPatientsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('patients')
            ->addColumn('status', 'enum', ['values' => ['active', 'archived', 'needs_review'], 'default' => 'active', 'after' => 'deceased_date'])
            ->save();
    }
}
