<?php

namespace App\Entity;

use App\Module\ClinicalReference\Repository\IcdCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IcdCodeRepository::class)]
#[ORM\Table(name: 'icd_codes')]
class IcdCode
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 50)] private ?string $code = null;
    #[ORM\Column(length: 255)] private ?string $description = null;
}
