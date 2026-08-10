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
#[ORM\Table(name: 'dictionary_values')]
class DictionaryValue
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $dictionary_id = null;
    #[ORM\Column(length: 255)] private ?string $value = null;
    #[ORM\Column(length: 255)] private ?string $label = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $order_num = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_active = true;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getDictionaryId() : ?int
    {
        return $this->dictionary_id;
    }

    public function setDictionaryId(?int $dictionary_id) : self
    {
        $this->dictionary_id = $dictionary_id;
        return $this;
    }

    public function getValue() : ?string
    {
        return $this->value;
    }

    public function setValue(?string $value) : self
    {
        $this->value = $value;
        return $this;
    }

    public function getLabel() : ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label) : self
    {
        $this->label = $label;
        return $this;
    }

    public function getOrderNum() : ?int
    {
        return $this->order_num;
    }

    public function setOrderNum(?int $order_num) : self
    {
        $this->order_num = $order_num;
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
