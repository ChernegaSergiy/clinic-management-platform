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

namespace App\Domain\Billing;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoices')]
class Invoice
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $patient_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $appointment_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $medical_record_id = null;
    #[ORM\Column(type: Types::FLOAT)] private ?float $amount = null;
    #[ORM\Column(length: 50)] private ?string $status = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $type = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $issued_date = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $paid_date = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $insurance_due = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $patient_due = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
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

    public function getAppointmentId() : ?int
    {
        return $this->appointment_id;
    }

    public function setAppointmentId(?int $appointment_id) : self
    {
        $this->appointment_id = $appointment_id;
        return $this;
    }

    public function getMedicalRecordId() : ?int
    {
        return $this->medical_record_id;
    }

    public function setMedicalRecordId(?int $medical_record_id) : self
    {
        $this->medical_record_id = $medical_record_id;
        return $this;
    }

    public function getAmount() : ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount) : self
    {
        $this->amount = $amount;
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

    public function getNotes() : ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes) : self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function setType(?string $type) : self
    {
        $this->type = $type;
        return $this;
    }

    public function getIssuedDate() : ?\DateTimeInterface
    {
        return $this->issued_date;
    }

    public function setIssuedDate(?\DateTimeInterface $issued_date) : self
    {
        $this->issued_date = $issued_date;
        return $this;
    }

    public function getPaidDate() : ?\DateTimeInterface
    {
        return $this->paid_date;
    }

    public function setPaidDate(?\DateTimeInterface $paid_date) : self
    {
        $this->paid_date = $paid_date;
        return $this;
    }

    public function getInsuranceDue() : ?float
    {
        return $this->insurance_due;
    }

    public function setInsuranceDue(?float $insurance_due) : self
    {
        $this->insurance_due = $insurance_due;
        return $this;
    }

    public function getPatientDue() : ?float
    {
        return $this->patient_due;
    }

    public function setPatientDue(?float $patient_due) : self
    {
        $this->patient_due = $patient_due;
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

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
}
