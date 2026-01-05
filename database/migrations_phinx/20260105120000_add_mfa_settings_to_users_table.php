<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMfaSettingsToUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');

        $table->addColumn('mfa_enabled', 'boolean', [
            'default' => false,
            'after' => 'password_hash',
        ]);

        $table->addColumn('mfa_type', 'enum', [
            'values' => ['totp', 'sms', 'email'],
            'default' => 'totp',
            'after' => 'mfa_enabled',
        ]);

        $table->addColumn('mfa_secret', 'string', [
            'limit' => 255,
            'null' => true,
            'after' => 'mfa_type',
        ]);

        $table->addColumn('mfa_backup_codes', 'json', [
            'null' => true,
            'after' => 'mfa_secret',
        ]);

        $table->addColumn('mfa_verified_at', 'datetime', [
            'null' => true,
            'after' => 'mfa_backup_codes',
        ]);

        $table->addColumn('mfa_pending', 'boolean', [
            'default' => false,
            'after' => 'mfa_verified_at',
        ]);

        $table->update();
    }
}
