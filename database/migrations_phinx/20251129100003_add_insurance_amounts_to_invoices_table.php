<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInsuranceAmountsToInvoicesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('invoices');
        $table->addColumn('insurance_due', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
              ->addColumn('patient_due', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
              ->update();
    }
}
