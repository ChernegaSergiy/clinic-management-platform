<?php

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    public function run()
    {
        $password_hash = password_hash('password', PASSWORD_DEFAULT);

        $data = [
            [
                'username' => 'admin',
                'password_hash' => $password_hash,
                'email' => 'admin@clinic.ua',
                'first_name' => 'Адмін',
                'last_name' => 'Адміненко',
                'role_id' => 1
            ],
            [
                'username' => 'doctor',
                'password_hash' => $password_hash,
                'email' => 'doctor@clinic.ua',
                'first_name' => 'Лікар',
                'last_name' => 'Лікаренко',
                'role_id' => 2
            ],
            [
                'username' => 'registrar',
                'password_hash' => $password_hash,
                'email' => 'registrar@clinic.ua',
                'first_name' => 'Реєстратор',
                'last_name' => 'Реєстраторенко',
                'role_id' => 3
            ]
        ];

        $users = $this->table('users');
        $users->insert($data)
              ->saveData();
    }
}
