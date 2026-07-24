<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Module\Billing\Repository\ServiceBundleRepository;

#[ORM\Entity(repositoryClass: ServiceBundleRepository::class)]
#[ORM\Table(name: 'service_bundles')]
class ServiceBundle
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: Types::FLOAT)] private ?float $price = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;
}
