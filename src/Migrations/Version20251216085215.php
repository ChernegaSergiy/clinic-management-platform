<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251216085215 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_doctor_schedules_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('doctor_schedules');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('doctor_id', 'integer', ['unsigned' => true]);
        $table->addColumn('day_of_week', 'integer');
        $table->addColumn('start_time', 'time');
        $table->addColumn('end_time', 'time');
        $table->addColumn('is_available', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['doctor_id', 'day_of_week']);
        $table->addForeignKeyConstraint('users', ['doctor_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('doctor_schedules');
    }
}
