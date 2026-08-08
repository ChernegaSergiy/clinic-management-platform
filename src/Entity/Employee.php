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

    public function getId() : ?int
    {
        return $this->id;
    }
    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
    public function getFirstName() : ?string
    {
        return $this->first_name;
    }
    public function setFirstName(?string $first_name) : self
    {
        $this->first_name = $first_name;
        return $this;
    }
    public function getLastName() : ?string
    {
        return $this->last_name;
    }
    public function setLastName(?string $last_name) : self
    {
        $this->last_name = $last_name;
        return $this;
    }
    public function getMiddleName() : ?string
    {
        return $this->middle_name;
    }
    public function setMiddleName(?string $middle_name) : self
    {
        $this->middle_name = $middle_name;
        return $this;
    }
    public function getPosition() : ?string
    {
        return $this->position;
    }
    public function setPosition(?string $position) : self
    {
        $this->position = $position;
        return $this;
    }
    public function getDepartmentId() : ?int
    {
        return $this->department_id;
    }
    public function setDepartmentId(?int $department_id) : self
    {
        $this->department_id = $department_id;
        return $this;
    }
    public function getHireDate() : ?\DateTimeInterface
    {
        return $this->hire_date;
    }
    public function setHireDate(?\DateTimeInterface $hire_date) : self
    {
        $this->hire_date = $hire_date;
        return $this;
    }
    public function getSalary() : ?float
    {
        return $this->salary;
    }
    public function setSalary(?float $salary) : self
    {
        $this->salary = $salary;
        return $this;
    }
    public function getContactPhone() : ?string
    {
        return $this->contact_phone;
    }
    public function setContactPhone(?string $contact_phone) : self
    {
        $this->contact_phone = $contact_phone;
        return $this;
    }
    public function getStatus() : ?string
    {
        return $this->status;
    }
    public function setStatus(?string $status) : self
    {
        $this->status = $status;
        return $this;
    }
    public function getUserId() : ?int
    {
        return $this->user_id;
    }
    public function setUserId(?int $user_id) : self
    {
        $this->user_id = $user_id;
        return $this;
    }
    public function getFireDate() : ?\DateTimeInterface
    {
        return $this->fire_date;
    }
    public function setFireDate(?\DateTimeInterface $fire_date) : self
    {
        $this->fire_date = $fire_date;
        return $this;
    }
}
