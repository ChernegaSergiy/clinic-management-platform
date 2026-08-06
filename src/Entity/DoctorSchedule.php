<?php

namespace App\Entity;

use App\Module\Schedule\Repository\DoctorScheduleRepository;
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
}
