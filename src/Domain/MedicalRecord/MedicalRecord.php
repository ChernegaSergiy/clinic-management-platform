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

namespace App\Domain\MedicalRecord;

use App\Domain\Appointment\Appointment;
use App\Domain\Patient\Patient;
use App\Domain\User\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicalRecordRepository::class)]
#[ORM\Table(name: 'medical_records')]
class MedicalRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: Appointment::class)]
    #[ORM\JoinColumn(name: 'appointment_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'doctor_id', referencedColumnName: 'id', nullable: false)]
    private ?User $doctor = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $visit_date = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $diagnosis_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $diagnosis_text = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $treatment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getPatient() : ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient) : static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getAppointment() : ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment) : static
    {
        $this->appointment = $appointment;
        return $this;
    }

    public function getDoctor() : ?User
    {
        return $this->doctor;
    }

    public function setDoctor(?User $doctor) : static
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getVisitDate() : ?\DateTimeInterface
    {
        return $this->visit_date;
    }

    public function setVisitDate(\DateTimeInterface $visitDate) : static
    {
        $this->visit_date = $visitDate;
        return $this;
    }

    public function getDiagnosisCode() : ?string
    {
        return $this->diagnosis_code;
    }

    public function setDiagnosisCode(?string $diagnosisCode) : static
    {
        $this->diagnosis_code = $diagnosisCode;
        return $this;
    }

    public function getDiagnosisText() : ?string
    {
        return $this->diagnosis_text;
    }

    public function setDiagnosisText(?string $diagnosisText) : static
    {
        $this->diagnosis_text = $diagnosisText;
        return $this;
    }

    public function getTreatment() : ?string
    {
        return $this->treatment;
    }

    public function setTreatment(?string $treatment) : static
    {
        $this->treatment = $treatment;
        return $this;
    }

    public function getNotes() : ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes) : static
    {
        $this->notes = $notes;
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

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
}
