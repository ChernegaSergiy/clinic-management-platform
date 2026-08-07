<?php

namespace App\Entity;

use App\Bundles\AdminBundle\Repository\BackupPolicyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BackupPolicyRepository::class)]
#[ORM\Table(name: 'backup_policies')]
class BackupPolicy
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $description = null;
    #[ORM\Column(length: 50)] private ?string $frequency = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $retention_days = null;
    #[ORM\Column(length: 50)] private ?string $status = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $last_run_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;
}
