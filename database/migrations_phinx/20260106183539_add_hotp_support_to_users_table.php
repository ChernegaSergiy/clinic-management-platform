<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHotpSupportToUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');

        $table->changeColumn('mfa_type', 'enum', [
            'values' => ['totp', 'hotp', 'sms', 'email'],
            'default' => 'totp',
        ]);

        $table->addColumn('mfa_counter', 'integer', [
            'default' => 0,
            'null' => true,
            'after' => 'mfa_backup_codes',
        ]);

        $table->update();
    }
}
