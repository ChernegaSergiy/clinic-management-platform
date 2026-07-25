<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121122200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_lab_order_resources_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('lab_order_resources');
        $table->addColumn('lab_order_id', 'integer', ['unsigned' => true]);
        $table->addColumn('lab_resource_id', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['lab_order_id', 'lab_resource_id']);
        $table->addIndex(['lab_order_id']);
        $table->addIndex(['lab_resource_id']);
        $table->addForeignKeyConstraint('lab_orders', ['lab_order_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('lab_resources', ['lab_resource_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('lab_order_resources');
    }
}
