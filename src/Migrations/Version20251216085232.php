<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251216085232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create_schedule_exceptions_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('schedule_exceptions');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('doctor_id', 'integer', ['unsigned' => true]);
        $table->addColumn('exception_date', 'date');
        $table->addColumn('start_time', 'time');
        $table->addColumn('end_time', 'time');
        $table->addColumn('is_available', 'boolean');
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['doctor_id', 'exception_date']);
        $table->addForeignKeyConstraint('users', ['doctor_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('schedule_exceptions');
    }
}
