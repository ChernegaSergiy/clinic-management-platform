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

namespace App\Entity;

use App\Bundles\HrmBundle\Repository\HrmRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HrmRepository::class)]
#[ORM\Table(name: 'employees')]
class Employee
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $first_name = null;
    #[ORM\Column(length: 255)] private ?string $last_name = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $middle_name = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $position = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $department_id = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $hire_date = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $salary = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $contact_phone = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $status = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $user_id = null;
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)] private ?\DateTimeInterface $fire_date = null;
}
