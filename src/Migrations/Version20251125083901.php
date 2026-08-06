<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251125083901 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_provider_to_users_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('users');
        $table->addColumn('provider', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('provider_id', 'string', ['length' => 255, 'notnull' => false]);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('users');
        $table->dropColumn('provider_id');
        $table->dropColumn('provider');
    }
}
