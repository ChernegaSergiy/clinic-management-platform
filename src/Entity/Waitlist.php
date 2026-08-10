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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'waitlists')]
class Waitlist
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $ticket_number = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $patient_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $desired_doctor_id = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $desired_start_time = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $desired_end_time = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $contact_phone = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $contact_email = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $status = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getTicketNumber() : ?string
    {
        return $this->ticket_number;
    }

    public function setTicketNumber(?string $ticket_number) : self
    {
        $this->ticket_number = $ticket_number;
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

    public function getDesiredDoctorId() : ?int
    {
        return $this->desired_doctor_id;
    }

    public function setDesiredDoctorId(?int $desired_doctor_id) : self
    {
        $this->desired_doctor_id = $desired_doctor_id;
        return $this;
    }

    public function getDesiredStartTime() : ?\DateTimeInterface
    {
        return $this->desired_start_time;
    }

    public function setDesiredStartTime(?\DateTimeInterface $desired_start_time) : self
    {
        $this->desired_start_time = $desired_start_time;
        return $this;
    }

    public function getDesiredEndTime() : ?\DateTimeInterface
    {
        return $this->desired_end_time;
    }

    public function setDesiredEndTime(?\DateTimeInterface $desired_end_time) : self
    {
        $this->desired_end_time = $desired_end_time;
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

    public function getContactPhone() : ?string
    {
        return $this->contact_phone;
    }

    public function setContactPhone(?string $contact_phone) : self
    {
        $this->contact_phone = $contact_phone;
        return $this;
    }

    public function getContactEmail() : ?string
    {
        return $this->contact_email;
    }

    public function setContactEmail(?string $contact_email) : self
    {
        $this->contact_email = $contact_email;
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
