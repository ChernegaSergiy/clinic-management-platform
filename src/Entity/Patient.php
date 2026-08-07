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

use App\Bundles\PatientBundle\Repository\PatientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\Table(name: 'patients')]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $middle_name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $birth_date = null;

    #[ORM\Column(length: 50)]
    private ?string $gender = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tax_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $document_id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $marital_status = null;

    #[ORM\Column(length: 50, options: ['default' => 'active'])]
    private string $status = 'active';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getFirstName() : ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $firstName) : static
    {
        $this->first_name = $firstName;
        return $this;
    }

    public function getLastName() : ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $lastName) : static
    {
        $this->last_name = $lastName;
        return $this;
    }

    public function getMiddleName() : ?string
    {
        return $this->middle_name;
    }

    public function setMiddleName(?string $middleName) : static
    {
        $this->middle_name = $middleName;
        return $this;
    }

    public function getBirthDate() : ?\DateTimeInterface
    {
        return $this->birth_date;
    }

    public function setBirthDate(\DateTimeInterface $birthDate) : static
    {
        $this->birth_date = $birthDate;
        return $this;
    }

    public function getGender() : ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender) : static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getPhone() : ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone) : static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email) : static
    {
        $this->email = $email;
        return $this;
    }

    public function getAddress() : ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address) : static
    {
        $this->address = $address;
        return $this;
    }

    public function getTaxId() : ?string
    {
        return $this->tax_id;
    }

    public function setTaxId(?string $taxId) : static
    {
        $this->tax_id = $taxId;
        return $this;
    }

    public function getDocumentId() : ?string
    {
        return $this->document_id;
    }

    public function setDocumentId(?string $documentId) : static
    {
        $this->document_id = $documentId;
        return $this;
    }

    public function getMaritalStatus() : ?string
    {
        return $this->marital_status;
    }

    public function setMaritalStatus(?string $maritalStatus) : static
    {
        $this->marital_status = $maritalStatus;
        return $this;
    }

    public function getStatus() : string
    {
        return $this->status;
    }

    public function setStatus(string $status) : static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt) : static
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt) : static
    {
        $this->updated_at = $updatedAt;
        return $this;
    }
}
