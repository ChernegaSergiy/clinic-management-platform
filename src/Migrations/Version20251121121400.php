<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121400 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_icd_codes_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('icd_codes');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 10]);
        $table->addColumn('description', 'text');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('icd_codes');
    }
}
