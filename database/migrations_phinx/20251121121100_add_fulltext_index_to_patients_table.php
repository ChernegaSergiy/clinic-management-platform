<?php
use Phinx\Migration\AbstractMigration;
class AddFulltextIndexToPatientsTable extends AbstractMigration
{
    public function up()
    {
        $this->table('patients')
            ->addIndex(['first_name', 'last_name', 'middle_name', 'address'], ['type' => 'fulltext'])
            ->save();
    }

    public function down()
    {
        $this->table('patients')
            ->removeIndex(['first_name', 'last_name', 'middle_name', 'address'])
            ->save();
    }
}
