<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDurationToServicesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('services');
        $table->addColumn('duration_minutes', 'integer', [
            'after' => 'price',
            'null' => false,
            'default' => 30,
            'comment' => 'Default duration of the service in minutes',
        ])
        ->update();
    }
}