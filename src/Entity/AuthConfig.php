<?php

namespace App\Entity;

use App\Module\Admin\Repository\AuthConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;
}
