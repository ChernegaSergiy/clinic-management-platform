<?php

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
}
