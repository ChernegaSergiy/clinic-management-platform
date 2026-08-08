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

use App\Bundles\AdminBundle\Repository\AuthConfigRepository;
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

    public function getId() : ?int
    {
        return $this->id;
    }
    public function getProvider() : ?string
    {
        return $this->provider;
    }
    public function setProvider(?string $provider) : self
    {
        $this->provider = $provider;
        return $this;
    }
    public function getClientId() : ?string
    {
        return $this->client_id;
    }
    public function setClientId(?string $client_id) : self
    {
        $this->client_id = $client_id;
        return $this;
    }
    public function getClientSecret() : ?string
    {
        return $this->client_secret;
    }
    public function setClientSecret(?string $client_secret) : self
    {
        $this->client_secret = $client_secret;
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
    public function getConfig() : ?string
    {
        return $this->config;
    }
    public function setConfig(?string $config) : self
    {
        $this->config = $config;
        return $this;
    }
    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->created_at;
    }
    public function setCreatedAt(?\DateTimeInterface $created_at) : self
    {
        $this->created_at = $created_at;
        return $this;
    }
    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updated_at;
    }
    public function setUpdatedAt(?\DateTimeInterface $updated_at) : self
    {
        $this->updated_at = $updated_at;
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
