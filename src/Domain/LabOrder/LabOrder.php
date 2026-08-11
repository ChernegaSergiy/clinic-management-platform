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

namespace App\Domain\LabOrder;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabOrderRepository::class)]
#[ORM\Table(name: 'lab_orders')]
class LabOrder
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

    #[ORM\Column(length: 255)]
    private ?string $order_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $results = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qr_code_hash = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated_at = null;

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

    public function getOrderCode() : ?string
    {
        return $this->order_code;
    }

    public function setOrderCode(?string $order_code) : self
    {
        $this->order_code = $order_code;
        return $this;
    }

    public function getResults() : ?string
    {
        return $this->results;
    }

    public function setResults(?string $results) : self
    {
        $this->results = $results;
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

    public function getStatus() : ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status) : self
    {
        $this->status = $status;
        return $this;
    }

    public function getQrCodeHash() : ?string
    {
        return $this->qr_code_hash;
    }

    public function setQrCodeHash(?string $qr_code_hash) : self
    {
        $this->qr_code_hash = $qr_code_hash;
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
