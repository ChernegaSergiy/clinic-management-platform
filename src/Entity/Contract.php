<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contracts')]
class Contract
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $title = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $start_date = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $end_date = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $party_a = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $party_b = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $file_path = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $status = null;
}
