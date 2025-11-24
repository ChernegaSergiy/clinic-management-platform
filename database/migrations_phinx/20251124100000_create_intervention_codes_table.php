<?php
use Phinx\Migration\AbstractMigration;
class CreateInterventionCodesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('intervention_codes', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('code', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addIndex(['code'], ['unique' => true])
              ->create();
    }
}
