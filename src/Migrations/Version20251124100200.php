<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251124100200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_ticket_and_contact_to_waitlists_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('waitlists');
        $table->addColumn('ticket_number', 'string', ['length' => 32, 'notnull' => false]);
        $table->addColumn('contact_phone', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('contact_email', 'string', ['length' => 191, 'notnull' => false]);
        $table->addUniqueIndex(['ticket_number']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('waitlists');
        $table->dropIndex(['ticket_number']);
        $table->dropColumn('contact_email');
        $table->dropColumn('contact_phone');
        $table->dropColumn('ticket_number');
    }
}
