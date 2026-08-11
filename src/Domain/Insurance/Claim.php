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

#[ORM\Entity(repositoryClass: ClaimRepository::class)]
#[ORM\Table(name: 'claims')]
class Claim
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $invoice_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $patient_policy_id = null;
    #[ORM\Column(length: 50)] private ?string $status = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $submitted_at = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $total_claimed = null;
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

    public function getInvoiceId() : ?int
    {
        return $this->invoice_id;
    }

    public function setInvoiceId(?int $invoice_id) : self
    {
        $this->invoice_id = $invoice_id;
        return $this;
    }

    public function getPatientPolicyId() : ?int
    {
        return $this->patient_policy_id;
    }

    public function setPatientPolicyId(?int $patient_policy_id) : self
    {
        $this->patient_policy_id = $patient_policy_id;
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

    public function getSubmittedAt() : ?\DateTimeInterface
    {
        return $this->submitted_at;
    }

    public function setSubmittedAt(?\DateTimeInterface $submitted_at) : self
    {
        $this->submitted_at = $submitted_at;
        return $this;
    }

    public function getTotalClaimed() : ?float
    {
        return $this->total_claimed;
    }

    public function setTotalClaimed(?float $total_claimed) : self
    {
        $this->total_claimed = $total_claimed;
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
