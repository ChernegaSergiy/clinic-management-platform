<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateClaimsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('claims');
        $table->addColumn('invoice_id', 'integer', ['signed' => false])
              ->addColumn('patient_policy_id', 'integer', ['signed' => false])
              ->addColumn('status', 'string', ['limit' => 50, 'default' => 'draft']) // e.g., draft, submitted, paid, denied
              ->addColumn('submitted_at', 'datetime', ['null' => true])
              ->addColumn('total_claimed', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('total_paid', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addTimestamps()
              ->addIndex(['status'])
              ->addForeignKey('invoice_id', 'invoices', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('patient_policy_id', 'patient_insurance_policies', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
