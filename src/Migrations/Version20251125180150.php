<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251125180150 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_user_oauth_identities_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('user_oauth_identities');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['unsigned' => true]);
        $table->addColumn('provider', 'string', ['length' => 50]);
        $table->addColumn('provider_id', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['user_id', 'provider', 'provider_id']);
        $table->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('user_oauth_identities');
    }
}
