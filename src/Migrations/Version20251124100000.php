<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251124100000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_intervention_codes_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('intervention_codes');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('intervention_codes');
    }
}
