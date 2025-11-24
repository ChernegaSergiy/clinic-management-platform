<?php
use Phinx\Migration\AbstractMigration;
class CreatePrescriptionItemsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('prescription_items', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('prescription_id', 'integer', ['signed' => false])
              ->addColumn('medication_name', 'string', ['limit' => 255])
              ->addColumn('dosage', 'string', ['limit' => 255])
              ->addColumn('frequency', 'string', ['limit' => 255])
              ->addColumn('duration', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addForeignKey('prescription_id', 'prescriptions', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['prescription_id'])
              ->create();
    }
}
