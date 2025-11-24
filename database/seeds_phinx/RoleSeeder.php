<?php

use Phinx\Seed\AbstractSeed;

class RoleSeeder extends AbstractSeed
{
    public function run()
    {
        $data = [
            [
                'name' => 'admin',
                'description' => 'Адміністратор системи'
            ],
            [
                'name' => 'doctor',
                'description' => 'Лікар'
            ],
            [
                'name' => 'registrar',
                'description' => 'Реєстратор'
            ]
        ];

        $roles = $this->table('roles');
        $roles->insert($data)
              ->saveData();
    }
}
