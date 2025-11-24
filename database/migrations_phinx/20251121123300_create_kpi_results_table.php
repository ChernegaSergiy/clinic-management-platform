<?php
use Phinx\Migration\AbstractMigration;
class CreateKpiResultsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('kpi_results', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('kpi_id', 'integer', ['signed' => false])
              ->addColumn('user_id', 'integer', ['signed' => false])
              ->addColumn('period_start', 'date')
              ->addColumn('period_end', 'date')
              ->addColumn('calculated_value', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('notes', 'text', ['null' => true])
              ->addTimestamps()
              ->addForeignKey('kpi_id', 'kpi_definitions', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['kpi_id', 'user_id', 'period_start', 'period_end'], ['unique' => true])
              ->addIndex(['kpi_id'])
              ->addIndex(['user_id'])
              ->create();
    }
}
