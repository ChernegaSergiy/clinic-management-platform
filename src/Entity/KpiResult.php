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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kpi_results')]
class KpiResult
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $kpi_id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $user_id = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $period_start = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $period_end = null;
    #[ORM\Column(type: Types::FLOAT)] private ?float $calculated_value = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }

    public function getKpiId() : ?int
    {
        return $this->kpi_id;
    }

    public function setKpiId(?int $kpi_id) : self
    {
        $this->kpi_id = $kpi_id;
        return $this;
    }

    public function getUserId() : ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id) : self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getPeriodStart() : ?\DateTimeInterface
    {
        return $this->period_start;
    }

    public function setPeriodStart(?\DateTimeInterface $period_start) : self
    {
        $this->period_start = $period_start;
        return $this;
    }

    public function getPeriodEnd() : ?\DateTimeInterface
    {
        return $this->period_end;
    }

    public function setPeriodEnd(?\DateTimeInterface $period_end) : self
    {
        $this->period_end = $period_end;
        return $this;
    }

    public function getCalculatedValue() : ?float
    {
        return $this->calculated_value;
    }

    public function setCalculatedValue(?float $calculated_value) : self
    {
        $this->calculated_value = $calculated_value;
        return $this;
    }

    public function getNotes() : ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes) : self
    {
        $this->notes = $notes;
        return $this;
    }
}
