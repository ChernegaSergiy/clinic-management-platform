<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129100000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_insurance_companies_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('insurance_companies');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('contact_person', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('phone', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('insurance_companies');
    }
}
