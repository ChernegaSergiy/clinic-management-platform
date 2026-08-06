<?php

namespace App\Entity;

use App\Module\Prescription\Repository\PrescriptionRepository;
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
}
