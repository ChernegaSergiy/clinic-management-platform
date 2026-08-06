<?php

namespace App\Entity;

use App\Module\Insurance\Repository\PatientInsurancePolicyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientInsurancePolicyRepository::class)]
#[ORM\Table(name: 'patient_insurance_policies')]
class PatientInsurancePolicy
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $patient_id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $insurance_company_id = null;
    #[ORM\Column(length: 255)] private ?string $policy_number = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $group_number = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $valid_from = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $valid_to = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;
}
