<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'dictionary_values')]
class DictionaryValue
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $dictionary_id = null;
    #[ORM\Column(length: 255)] private ?string $value = null;
    #[ORM\Column(length: 255)] private ?string $label = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $order_num = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;
}
