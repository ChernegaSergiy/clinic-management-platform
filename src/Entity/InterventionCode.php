<?php

namespace App\Entity;

use App\Bundles\ClinicalReferenceBundle\Repository\InterventionCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionCodeRepository::class)]
#[ORM\Table(name: 'intervention_codes')]
class InterventionCode
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 50)] private ?string $code = null;
    #[ORM\Column(length: 255)] private ?string $description = null;
}
