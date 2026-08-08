<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Entity;

use App\Bundles\InventoryBundle\Repository\InventoryItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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

    public function getId() : ?int
    {
        return $this->id;
    }
    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
    public function getName() : ?string
    {
        return $this->name;
    }
    public function setName(?string $name) : self
    {
        $this->name = $name;
        return $this;
    }
    public function getDescription() : ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description) : self
    {
        $this->description = $description;
        return $this;
    }
    public function getInn() : ?string
    {
        return $this->inn;
    }
    public function setInn(?string $inn) : self
    {
        $this->inn = $inn;
        return $this;
    }
    public function getBatchNumber() : ?string
    {
        return $this->batch_number;
    }
    public function setBatchNumber(?string $batch_number) : self
    {
        $this->batch_number = $batch_number;
        return $this;
    }
    public function getExpiryDate() : ?\DateTimeInterface
    {
        return $this->expiry_date;
    }
    public function setExpiryDate(?\DateTimeInterface $expiry_date) : self
    {
        $this->expiry_date = $expiry_date;
        return $this;
    }
    public function getSupplier() : ?string
    {
        return $this->supplier;
    }
    public function setSupplier(?string $supplier) : self
    {
        $this->supplier = $supplier;
        return $this;
    }
    public function getCost() : ?string
    {
        return $this->cost;
    }
    public function setCost(?string $cost) : self
    {
        $this->cost = $cost;
        return $this;
    }
    public function getQuantity() : ?int
    {
        return $this->quantity;
    }
    public function setQuantity(?int $quantity) : self
    {
        $this->quantity = $quantity;
        return $this;
    }
    public function getMinStockLevel() : ?int
    {
        return $this->min_stock_level;
    }
    public function setMinStockLevel(?int $min_stock_level) : self
    {
        $this->min_stock_level = $min_stock_level;
        return $this;
    }
    public function getMaxStockLevel() : ?int
    {
        return $this->max_stock_level;
    }
    public function setMaxStockLevel(?int $max_stock_level) : self
    {
        $this->max_stock_level = $max_stock_level;
        return $this;
    }
    public function getLocation() : ?string
    {
        return $this->location;
    }
    public function setLocation(?string $location) : self
    {
        $this->location = $location;
        return $this;
    }
}
