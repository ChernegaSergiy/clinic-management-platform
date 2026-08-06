<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_audit_logs_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('audit_logs');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('entity_type', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('user_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('action', 'string', ['length' => 255]);
        $table->addColumn('old_value', 'text', ['notnull' => false]);
        $table->addColumn('new_value', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_type', 'entity_id']);
        $table->addIndex(['user_id']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('audit_logs');
    }
}
