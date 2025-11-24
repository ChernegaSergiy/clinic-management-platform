<?php
use Phinx\Migration\AbstractMigration;
class CreateInterventionCodesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('intervention_codes');
        $table->addColumn('code', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addIndex(['code'], ['unique' => true])
              ->create();
    }
}
