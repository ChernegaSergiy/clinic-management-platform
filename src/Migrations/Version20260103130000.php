<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260103130000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_room_id_to_appointments_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('appointments');
        $table->addColumn('room_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addIndex(['room_id']);
        $table->addForeignKeyConstraint('rooms', ['room_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('appointments');
        $table->removeForeignKeyConstraint('room_id');
        $table->dropIndex('room_id');
        $table->dropColumn('room_id');
    }
}
