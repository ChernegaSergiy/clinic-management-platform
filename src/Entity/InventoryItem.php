<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Module\Inventory\Repository\InventoryItemRepository;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\Table(name: 'inventory_items')]
class InventoryItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $inn = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $batch_number = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiry_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $supplier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $cost = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $quantity = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $min_stock_level = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $max_stock_level = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;
}
