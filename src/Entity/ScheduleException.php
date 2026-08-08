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

use App\Bundles\ScheduleBundle\Repository\ScheduleExceptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleExceptionRepository::class)]
#[ORM\Table(name: 'schedule_exceptions')]
class ScheduleException
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $doctor_id = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $exception_date = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $start_time = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $end_time = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_available = true;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;

    public function getId() : ?int
    {
        return $this->id;
    }
    public function setId(?int $id) : self
    {
        $this->id = $id;
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
    public function getExceptionDate() : ?\DateTimeInterface
    {
        return $this->exception_date;
    }
    public function setExceptionDate(?\DateTimeInterface $exception_date) : self
    {
        $this->exception_date = $exception_date;
        return $this;
    }
    public function getStartTime() : ?\DateTimeInterface
    {
        return $this->start_time;
    }
    public function setStartTime(?\DateTimeInterface $start_time) : self
    {
        $this->start_time = $start_time;
        return $this;
    }
    public function getEndTime() : ?\DateTimeInterface
    {
        return $this->end_time;
    }
    public function setEndTime(?\DateTimeInterface $end_time) : self
    {
        $this->end_time = $end_time;
        return $this;
    }
    public function isIsAvailable() : bool
    {
        return $this->is_available;
    }
    public function setIsAvailable(bool $is_available) : self
    {
        $this->is_available = $is_available;
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
}
