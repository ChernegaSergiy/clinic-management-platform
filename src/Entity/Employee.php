<?php

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
