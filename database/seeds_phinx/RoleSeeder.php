<?php

use Phinx\Seed\AbstractSeed;

class RoleSeeder extends AbstractSeed
{
    public function run(): void
    {
        $roles_to_seed = [
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
            ],
            [
                'name' => 'hr_manager',
                'description' => 'HR-менеджер / Менеджер з персоналу'
            ]
        ];

        $rolesTable = $this->table('roles');

        $existingRoles = $this->fetchAll('SELECT name FROM roles');
        $existingRoleNames = array_column($existingRoles, 'name');

        $dataToInsert = [];
        foreach ($roles_to_seed as $role) {
            if (!in_array($role['name'], $existingRoleNames)) {
                $dataToInsert[] = $role;
            }
        }

        if (!empty($dataToInsert)) {
            $rolesTable->insert($dataToInsert)->saveData();
        }
    }
}
