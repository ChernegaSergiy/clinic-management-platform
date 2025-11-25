<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserOAuthIdentitiesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user_oauth_identities', ['id' => true, 'primary_key' => 'id']);
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('provider', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('provider_id', 'string', ['limit' => 255, 'null' => false])
              ->addTimestamps()
              ->addIndex(['user_id', 'provider', 'provider_id'], ['unique' => true])
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}