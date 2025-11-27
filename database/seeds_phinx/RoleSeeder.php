<?php

use Phinx\Seed\AbstractSeed;

class RoleSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'admin',
                'description' => 'Адміністратор системи'
            ],
            [
                'name' => 'medical_manager',
                'description' => 'Медичний керівник / методолог'
            ],
            [
                'name' => 'doctor',
                'description' => 'Лікар'
            ],
            [
                'name' => 'registrar',
                'description' => 'Реєстратор'
            ],
            [
                'name' => 'nurse',
                'description' => 'Медсестра / асистент'
            ],
            [
                'name' => 'lab_technician',
                'description' => 'Лаборант'
            ],
            [
                'name' => 'billing',
                'description' => 'Білінг / бухгалтерія'
            ],
            [
                'name' => 'inventory_manager',
                'description' => 'Комірник / менеджер складу'
            ]
        ];

        $roles = $this->table('roles');
        $roles->insert($data)
              ->saveData();
    }
}
