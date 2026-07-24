<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Module\Admin\Repository\AuthConfigRepository;

#[ORM\Entity(repositoryClass: AuthConfigRepository::class)]
#[ORM\Table(name: 'auth_configs')]
class AuthConfig
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $provider = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $client_id = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $client_secret = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $config = null;
}
