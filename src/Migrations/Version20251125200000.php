<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251125200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_waitlist_id_to_appointments';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('appointments');
        $table->addColumn('waitlist_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addIndex(['waitlist_id']);
        $table->addForeignKeyConstraint('waitlists', ['waitlist_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('appointments');
        $table->removeForeignKeyConstraint('waitlist_id');
        $table->dropIndex(['waitlist_id']);
        $table->dropColumn('waitlist_id');
    }
}
