<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveDepartmentTextFromEmployeesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('employees');
        if ($table->hasColumn('department')) {
            $table->removeColumn('department')->update();
        }
    }
}
