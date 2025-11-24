<?php
use Phinx\Migration\AbstractMigration;
class AddTypeToInvoicesTable extends AbstractMigration
{
    public function change()
    {
        $this->table('invoices')
            ->addColumn('type', 'enum', ['values' => ['invoice', 'inventory_cost', 'inventory_revenue'], 'default' => 'invoice', 'after' => 'status'])
            ->save();
    }
}
