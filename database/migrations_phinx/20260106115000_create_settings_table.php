<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSettingsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('settings', ['id' => false, 'primary_key' => 'key']);

        $table->addColumn('key', 'string', ['limit' => 100])
              ->addColumn('value', 'text', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->create();

        $this->table('settings')->insert([
            ['key' => 'mfa_policy', 'value' => 'optional'],
            ['key' => 'mfa_force_roles', 'value' => null],
        ])->saveData();
    }
}
