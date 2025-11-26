<?php

use Phinx\Migration\AbstractMigration;

class AddUtilizationAndReadmissionKpiTypes extends AbstractMigration
{
    public function up()
    {
        $this->table('kpi_definitions')
            ->changeColumn('kpi_type', 'enum', [
                'values' => [
                    'appointments_count',
                    'revenue_generated',
                    'patient_satisfaction',
                    'doctor_utilization',
                    'readmission_rate'
                ]
            ])
            ->update();
    }

    public function down()
    {
        $this->table('kpi_definitions')
            ->changeColumn('kpi_type', 'enum', [
                'values' => [
                    'appointments_count',
                    'revenue_generated',
                    'patient_satisfaction'
                ]
            ])
            ->update();
    }
}