<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDepartmentsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('departments');
        $table->addColumn('name', 'string', ['limit' => 255, 'comment' => 'Назва відділення (напр. Кардіологія)'])
            ->addColumn('description', 'text', ['null' => true, 'comment' => 'Опис функцій відділення'])
            ->addColumn('parent_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'Для ієрархії (підрозділи)'])
            ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Активний статус'])
            ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => 'Порядок сортування'])
            ->addTimestamps()
            ->addIndex(['is_active'])
            ->addIndex(['sort_order'])
            ->addForeignKey('parent_id', 'departments', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addIndex(['name'], ['unique' => true])
            ->create();
    }
}