<?php

namespace App\Entity;

use App\Module\LabOrder\Repository\LabOrderRepository;
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
}
