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

#[ORM\Entity(repositoryClass: PatientInsurancePolicyRepository::class)]
#[ORM\Table(name: 'patient_insurance_policies')]
class PatientInsurancePolicy
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $patient_id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $insurance_company_id = null;
    #[ORM\Column(length: 255)] private ?string $policy_number = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $group_number = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $valid_from = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $valid_to = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;
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

    public function getPatientId() : ?int
    {
        return $this->patient_id;
    }

    public function setPatientId(?int $patient_id) : self
    {
        $this->patient_id = $patient_id;
        return $this;
    }

    public function getInsuranceCompanyId() : ?int
    {
        return $this->insurance_company_id;
    }

    public function setInsuranceCompanyId(?int $insurance_company_id) : self
    {
        $this->insurance_company_id = $insurance_company_id;
        return $this;
    }

    public function getPolicyNumber() : ?string
    {
        return $this->policy_number;
    }

    public function setPolicyNumber(?string $policy_number) : self
    {
        $this->policy_number = $policy_number;
        return $this;
    }

    public function getGroupNumber() : ?string
    {
        return $this->group_number;
    }

    public function setGroupNumber(?string $group_number) : self
    {
        $this->group_number = $group_number;
        return $this;
    }

    public function getValidFrom() : ?\DateTimeInterface
    {
        return $this->valid_from;
    }

    public function setValidFrom(?\DateTimeInterface $valid_from) : self
    {
        $this->valid_from = $valid_from;
        return $this;
    }

    public function getValidTo() : ?\DateTimeInterface
    {
        return $this->valid_to;
    }

    public function setValidTo(?\DateTimeInterface $valid_to) : self
    {
        $this->valid_to = $valid_to;
        return $this;
    }

    public function isIsActive() : bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active) : self
    {
        $this->is_active = $is_active;
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
