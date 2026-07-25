<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126152844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_notes_to_lab_orders_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('lab_orders');
        $table->addColumn('notes', 'text', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('lab_orders');
        $table->dropColumn('notes');
    }
}
