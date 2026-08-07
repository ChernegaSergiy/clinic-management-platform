<?php

namespace App\Entity;

use App\Bundles\AdminBundle\Repository\DictionaryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DictionaryRepository::class)]
#[ORM\Table(name: 'dictionaries')]
class Dictionary
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $description = null;
    #[ORM\Column(length: 50)] private ?string $type = null;
}
