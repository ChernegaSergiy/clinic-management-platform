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

namespace App\Domain\Prescription;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionRepository::class)]
#[ORM\Table(name: 'prescriptions')]
class Prescription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $patient_id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $doctor_id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $medical_record_id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $issue_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiry_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

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

    public function getDoctorId() : ?int
    {
        return $this->doctor_id;
    }

    public function setDoctorId(?int $doctor_id) : self
    {
        $this->doctor_id = $doctor_id;
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

    public function getIssueDate() : ?\DateTimeInterface
    {
        return $this->issue_date;
    }

    public function setIssueDate(?\DateTimeInterface $issue_date) : self
    {
        $this->issue_date = $issue_date;
        return $this;
    }

    public function getExpiryDate() : ?\DateTimeInterface
    {
        return $this->expiry_date;
    }

    public function setExpiryDate(?\DateTimeInterface $expiry_date) : self
    {
        $this->expiry_date = $expiry_date;
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
}
