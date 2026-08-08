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

use App\Bundles\UserBundle\Repository\UserOAuthIdentityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserOAuthIdentityRepository::class)]
#[ORM\Table(name: 'user_oauth_identities')]
#[ORM\UniqueConstraint(name: 'provider_user', columns: ['provider', 'provider_id'])]
class UserOAuthIdentity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $provider = null;

    #[ORM\Column(length: 255)]
    private ?string $provider_id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getUser() : ?User
    {
        return $this->user;
    }

    public function setUser(?User $user) : static
    {
        $this->user = $user;
        return $this;
    }

    public function getProvider() : ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider) : static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProviderId() : ?string
    {
        return $this->provider_id;
    }

    public function setProviderId(string $providerId) : static
    {
        $this->provider_id = $providerId;
        return $this;
    }

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt) : static
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
}
