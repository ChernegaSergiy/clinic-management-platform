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

use App\Bundles\KpiBundle\Repository\KpiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KpiRepository::class)]
#[ORM\Table(name: 'kpi_definitions')]
class KpiDefinition
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $name = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $description = null;
    #[ORM\Column(length: 50)] private ?string $kpi_type = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $target_value = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $unit = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;
    #[ORM\Column(length: 50, nullable: true)] private ?string $period = null;

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

    public function getKpiType() : ?string
    {
        return $this->kpi_type;
    }

    public function setKpiType(?string $kpi_type) : self
    {
        $this->kpi_type = $kpi_type;
        return $this;
    }

    public function getTargetValue() : ?float
    {
        return $this->target_value;
    }

    public function setTargetValue(?float $target_value) : self
    {
        $this->target_value = $target_value;
        return $this;
    }

    public function getUnit() : ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit) : self
    {
        $this->unit = $unit;
        return $this;
    }

    public function isIsActive() : bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active) : self
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getPeriod() : ?string
    {
        return $this->period;
    }

    public function setPeriod(?string $period) : self
    {
        $this->period = $period;
        return $this;
    }
}
