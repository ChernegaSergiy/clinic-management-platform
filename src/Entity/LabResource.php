<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lab_resources')]
class LabResource
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(length: 50)] private ?string $type = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $capacity = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_available = true;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
}
