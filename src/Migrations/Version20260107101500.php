<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove_department_text_from_employees_table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('employees');
        if ($table->hasColumn('department')) {
            $table->dropColumn('department');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('employees');
        $table->addColumn('department', 'string', ['length' => 100, 'notnull' => false]);
    }
}
