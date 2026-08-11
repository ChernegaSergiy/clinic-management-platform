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

namespace App\Domain\Insurance;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InsuranceCompanyRepository::class)]
#[ORM\Table(name: 'insurance_companies')]
class InsuranceCompany
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $contact_person = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $phone = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $email = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(?string $name) : self
    {
        $this->name = $name;
        return $this;
    }

    public function getContactPerson() : ?string
    {
        return $this->contact_person;
    }

    public function setContactPerson(?string $contact_person) : self
    {
        $this->contact_person = $contact_person;
        return $this;
    }

    public function getPhone() : ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone) : self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email) : self
    {
        $this->email = $email;
        return $this;
    }

    public function getNotes() : ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes) : self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at) : self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at) : self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
}
