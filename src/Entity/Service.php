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

use App\Bundles\BillingBundle\Repository\ServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
class Service
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: Types::FLOAT)] private ?float $price = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $category_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $duration_minutes = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;

    public function getId() : ?int
    {
        return $this->id;
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

    public function getPrice() : ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price) : self
    {
        $this->price = $price;
        return $this;
    }

    public function getCategoryId() : ?int
    {
        return $this->category_id;
    }

    public function setCategoryId(?int $category_id) : self
    {
        $this->category_id = $category_id;
        return $this;
    }

    public function getDurationMinutes() : ?int
    {
        return $this->duration_minutes;
    }

    public function setDurationMinutes(?int $duration_minutes) : self
    {
        $this->duration_minutes = $duration_minutes;
        return $this;
    }

    public function isActive() : bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active) : self
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }

    public function isIsActive() : bool
    {
        return $this->is_active;
    }
}
