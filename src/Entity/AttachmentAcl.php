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

use App\Shared\Repository\AttachmentAclRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttachmentAclRepository::class)]
#[ORM\Table(name: 'attachment_acl')]
#[ORM\UniqueConstraint(name: 'unique_acl', columns: ['attachment_id', 'user_id', 'role_id'])]
class AttachmentAcl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // Doctrine assigns this via reflection when persisting a new entity, which
    // PHPStan cannot see, so it otherwise flags the int part of the type as unused.
    /** @phpstan-ignore-next-line property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(name: 'attachment_id', referencedColumnName: 'id', nullable: false)]
    private ?Attachment $attachment = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $user_id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $role_id = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $can_view = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $can_edit = false;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getAttachment() : ?Attachment
    {
        return $this->attachment;
    }

    public function setAttachment(?Attachment $attachment) : static
    {
        $this->attachment = $attachment;
        return $this;
    }

    public function getUserId() : ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id) : static
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getRoleId() : ?int
    {
        return $this->role_id;
    }

    public function setRoleId(?int $role_id) : static
    {
        $this->role_id = $role_id;
        return $this;
    }

    public function isCanView() : ?bool
    {
        return $this->can_view;
    }

    public function setCanView(bool $can_view) : static
    {
        $this->can_view = $can_view;
        return $this;
    }

    public function isCanEdit() : ?bool
    {
        return $this->can_edit;
    }

    public function setCanEdit(bool $can_edit) : static
    {
        $this->can_edit = $can_edit;
        return $this;
    }
}
