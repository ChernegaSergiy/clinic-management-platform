<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Module\Kpi\Repository\KpiRepository;

#[ORM\Entity(repositoryClass: KpiRepository::class)]
#[ORM\Table(name: 'kpi_definitions')]
class KpiDefinition
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $description = null;
    #[ORM\Column(length: 50)] private ?string $kpi_type = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $target_value = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $unit = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;
    #[ORM\Column(length: 50, nullable: true)] private ?string $period = null;
}
