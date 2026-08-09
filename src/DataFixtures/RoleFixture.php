<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixture extends Fixture
{
    public function load(ObjectManager $manager) : void
    {
        $roles = [
            ['name' => 'ROLE_ADMIN', 'description' => 'Адміністратор системи'],
            ['name' => 'ROLE_MEDICAL_MANAGER', 'description' => 'Медичний керівник / методолог'],
            ['name' => 'ROLE_DOCTOR', 'description' => 'Лікар'],
            ['name' => 'ROLE_REGISTRAR', 'description' => 'Реєстратор'],
            ['name' => 'ROLE_NURSE', 'description' => 'Медсестра / асистент'],
            ['name' => 'ROLE_LAB_TECHNICIAN', 'description' => 'Лаборант'],
            ['name' => 'ROLE_BILLING', 'description' => 'Білінг / бухгалтерія'],
            ['name' => 'ROLE_INVENTORY_MANAGER', 'description' => 'Комірник / менеджер складу'],
            ['name' => 'ROLE_HR_MANAGER', 'description' => 'HR-менеджер / Менеджер з персоналу'],
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
