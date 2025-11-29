<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInsuranceCompaniesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('insurance_companies');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('contact_person', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('phone', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addTimestamps() // Adds created_at and updated_at
              ->create();
    }
}
