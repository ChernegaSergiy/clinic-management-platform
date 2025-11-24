<?php
use Phinx\Migration\AbstractMigration;
class CreatePrescriptionItemsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('prescription_items');
        $table->addColumn('prescription_id', 'integer')
              ->addColumn('medication_name', 'string', ['limit' => 255])
              ->addColumn('dosage', 'string', ['limit' => 255])
              ->addColumn('frequency', 'string', ['limit' => 255])
              ->addColumn('duration', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addForeignKey('prescription_id', 'prescriptions', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
