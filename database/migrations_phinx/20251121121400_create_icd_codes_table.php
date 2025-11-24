<?php
use Phinx\Migration\AbstractMigration;
class CreateIcdCodesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('icd_codes');
        $table->addColumn('code', 'string', ['limit' => 10])
              ->addColumn('description', 'text')
              ->addIndex(['code'], ['unique' => true])
              ->create();
    }
}
