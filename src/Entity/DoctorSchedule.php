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

use App\Bundles\ScheduleBundle\Repository\DoctorScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctorScheduleRepository::class)]
#[ORM\Table(name: 'doctor_schedules')]
class DoctorSchedule
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $doctor_id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $day_of_week = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $start_time = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $end_time = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_available = true;

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
    public function getDayOfWeek() : ?int
    {
        return $this->day_of_week;
    }
    public function setDayOfWeek(?int $day_of_week) : self
    {
        $this->day_of_week = $day_of_week;
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
}
