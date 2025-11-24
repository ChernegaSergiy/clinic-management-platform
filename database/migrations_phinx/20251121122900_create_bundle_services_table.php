<?php
use Phinx\Migration\AbstractMigration;
class CreateBundleServicesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('bundle_services', ['id' => false, 'primary_key' => ['bundle_id', 'service_id']]);
        $table->addColumn('bundle_id', 'integer', ['signed' => false])
              ->addColumn('service_id', 'integer', ['signed' => false])
              ->addForeignKey('bundle_id', 'service_bundles', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('service_id', 'services', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['bundle_id'])
              ->addIndex(['service_id'])
              ->create();
    }
}
