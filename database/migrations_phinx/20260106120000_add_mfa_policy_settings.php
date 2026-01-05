<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMfaPolicySettings extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('settings');

        $table->addColumn('mfa_policy', 'enum', [
            'values' => ['optional', 'admin_required', 'all_required', 'disabled'],
            'default' => 'optional',
        ]);

        $table->addColumn('mfa_force_roles', 'json', [
            'null' => true,
        ]);

        $table->update();
    }
}
