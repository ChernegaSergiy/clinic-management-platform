<?php

namespace App\Entity;

use App\Bundles\InsuranceBundle\Repository\ClaimRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClaimRepository::class)]
#[ORM\Table(name: 'claims')]
class Claim
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $invoice_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $patient_policy_id = null;
    #[ORM\Column(length: 50)] private ?string $status = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $submitted_at = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $total_claimed = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;
}
