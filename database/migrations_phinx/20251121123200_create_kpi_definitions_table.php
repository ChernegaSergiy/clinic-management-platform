<?php
use Phinx\Migration\AbstractMigration;
class CreateKpiDefinitionsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('kpi_definitions');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('kpi_type', 'enum', ['values' => ['appointments_count', 'revenue_generated', 'patient_satisfaction']])
              ->addColumn('target_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('unit', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addTimestamps()
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
