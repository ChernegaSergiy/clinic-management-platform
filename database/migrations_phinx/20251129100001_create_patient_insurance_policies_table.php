<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePatientInsurancePoliciesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('patient_insurance_policies');
        $table->addColumn('patient_id', 'integer', ['signed' => false])
              ->addColumn('insurance_company_id', 'integer', ['signed' => false])
              ->addColumn('policy_number', 'string', ['limit' => 255])
              ->addColumn('group_number', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('valid_from', 'date')
              ->addColumn('valid_to', 'date', ['null' => true])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addTimestamps()
              ->addForeignKey('patient_id', 'patients', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('insurance_company_id', 'insurance_companies', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->create();
    }
}
