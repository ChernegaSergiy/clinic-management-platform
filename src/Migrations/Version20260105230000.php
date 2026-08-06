<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260105230000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'update_employees_add_department_id';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('employees');
        $table->addColumn('department_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addForeignKeyConstraint('departments', ['department_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => 'NO_ACTION']);

        $this->addSql("
            UPDATE employees e 
            SET e.department_id = (
                SELECT d.id FROM departments d 
                WHERE LOWER(TRIM(d.name)) = LOWER(TRIM(e.department)) 
                LIMIT 1
            )
            WHERE e.department IS NOT NULL 
            AND e.department != ''
            AND e.department_id IS NULL
        ");
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('employees');
        $table->removeForeignKeyConstraint('department_id');
        $table->dropColumn('department_id');
    }
}
