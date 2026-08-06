<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121122500 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_type_to_invoices_table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE invoices ADD type ENUM(\'invoice\', \'inventory_cost\', \'inventory_revenue\') DEFAULT \'invoice\' NOT NULL AFTER status');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE invoices DROP type');
    }
}
