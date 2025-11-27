<?php

use Phinx\Migration\AbstractMigration;

class AddKpiPeriod extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kpi_definitions');
        if (!$table->hasColumn('period')) {
            $table->addColumn('period', 'enum', [
                'values' => ['day', 'week', 'month'],
                'default' => 'day',
                'null' => false,
            ])->update();
        }
    }
}
