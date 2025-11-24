<?php
use Phinx\Migration\AbstractMigration;
class CreatePaymentsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('payments', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('invoice_id', 'integer', ['signed' => false])
              ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('payment_method', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('transaction_id', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('payment_date', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('notes', 'text', ['null' => true])
              ->addForeignKey('invoice_id', 'invoices', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['invoice_id'])
              ->create();
    }
}
