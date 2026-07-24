<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kpi_results')]
class KpiResult
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $kpi_id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $user_id = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $period_start = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $period_end = null;
    #[ORM\Column(type: Types::FLOAT)] private ?float $calculated_value = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
}
