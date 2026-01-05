<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateEmployeesAddDepartmentId extends AbstractMigration
{
    public function change(): void
    {
        // Додавання department_id колонки
        $table = $this->table('employees');
        $table->addColumn('department_id', 'integer', [
            'signed' => false, 
            'null' => true, 
            'comment' => 'ID відділення (foreign key)'
        ]);
        
        // Створення foreign key до departments таблиці
        $table->addForeignKey('department_id', 'departments', 'id', [
            'delete' => 'SET_NULL', 
            'update' => 'NO_ACTION'
        ]);
        
        $table->update();
        
        // Міграція існуючих даних з текстового поля department в department_id
        $this->migrateExistingDepartmentData();
    }
    
    private function migrateExistingDepartmentData(): void
    {
        $pdo = $this->getAdapter()->getConnection();
        
        // Міграція даних на основі співпадіння назв
        $sql = "
            UPDATE employees e 
            SET e.department_id = (
                SELECT d.id FROM departments d 
                WHERE LOWER(TRIM(d.name)) = LOWER(TRIM(e.department)) 
                LIMIT 1
            )
            WHERE e.department IS NOT NULL 
            AND e.department != ''
            AND e.department_id IS NULL
        ";
        
        $pdo->exec($sql);
    }
}