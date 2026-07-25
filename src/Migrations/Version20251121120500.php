<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_default_status_to_appointments';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('appointments');
        $table->getColumn('status')->setOptions(['default' => 'scheduled']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('appointments');
        $table->getColumn('status')->setOptions(['default' => null]);
    }
}
