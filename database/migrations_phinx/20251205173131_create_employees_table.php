<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmployeesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // create the table
        $table = $this->table('employees');
        $table->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('first_name', 'string', ['limit' => 100])
            ->addColumn('last_name', 'string', ['limit' => 100])
            ->addColumn('middle_name', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('position', 'string', ['limit' => 100, 'comment' => 'e.g., Doctor, Nurse, Admin'])
            ->addColumn('department', 'string', ['limit' => 100, 'null' => true, 'comment' => 'e.g., Cardiology, Surgery'])
            ->addColumn('hire_date', 'date')
            ->addColumn('fire_date', 'date', ['null' => true])
            ->addColumn('salary', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('contact_phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'active', 'comment' => 'e.g., active, on_leave, terminated'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addIndex(['last_name', 'first_name'])
            ->addIndex(['status'])
            ->create();
    }
}
