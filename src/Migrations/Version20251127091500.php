<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_kpi_period';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kpi_definitions');
        $table->addColumn('period', 'string', ['length' => 10, 'default' => 'day']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kpi_definitions');
        $table->dropColumn('period');
    }
}
