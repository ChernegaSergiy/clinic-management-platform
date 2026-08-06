<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129100003 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_insurance_amounts_to_invoices_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('invoices');
        $table->addColumn('insurance_due', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => '0.00']);
        $table->addColumn('patient_due', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => '0.00']);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('invoices');
        $table->dropColumn('insurance_due');
        $table->dropColumn('patient_due');
    }
}
