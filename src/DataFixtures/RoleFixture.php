<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Адміністратор системи'],
            ['name' => 'medical_manager', 'description' => 'Медичний керівник / методолог'],
            ['name' => 'doctor', 'description' => 'Лікар'],
            ['name' => 'registrar', 'description' => 'Реєстратор'],
            ['name' => 'nurse', 'description' => 'Медсестра / асистент'],
            ['name' => 'lab_technician', 'description' => 'Лаборант'],
            ['name' => 'billing', 'description' => 'Білінг / бухгалтерія'],
            ['name' => 'inventory_manager', 'description' => 'Комірник / менеджер складу'],
            ['name' => 'hr_manager', 'description' => 'HR-менеджер / Менеджер з персоналу'],
        ];

        foreach ($roles as $data) {
            $role = new Role();
            $role->setName($data['name']);
            $role->setDescription($data['description']);
            $manager->persist($role);
        }

        $manager->flush();
    }
}
