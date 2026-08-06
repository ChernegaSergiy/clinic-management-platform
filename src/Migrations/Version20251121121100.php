<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121100 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add_fulltext_index_to_patients_table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE patients ADD FULLTEXT INDEX idx_patients_fulltext (first_name, last_name, middle_name, address)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE patients DROP INDEX idx_patients_fulltext');
    }
}
