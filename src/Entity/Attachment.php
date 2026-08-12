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

use App\Shared\Repository\AttachmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'attachments')]
class Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // Doctrine assigns this via reflection when persisting a new entity, which
    // PHPStan cannot see, so it otherwise flags the int part of the type as unused.
    /** @phpstan-ignore-next-line property.unusedType */
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $entity_type = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $entity_id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 500)]
    private ?string $filepath = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mime_type = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $size = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getEntityType() : ?string
    {
        return $this->entity_type;
    }

    public function setEntityType(string $entity_type) : static
    {
        $this->entity_type = $entity_type;
        return $this;
    }

    public function getEntityId() : ?int
    {
        return $this->entity_id;
    }

    public function setEntityId(int $entity_id) : static
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    public function getFilename() : ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename) : static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getFilepath() : ?string
    {
        return $this->filepath;
    }

    public function setFilepath(string $filepath) : static
    {
        $this->filepath = $filepath;
        return $this;
    }

    public function getMimeType() : ?string
    {
        return $this->mime_type;
    }

    public function setMimeType(?string $mime_type) : static
    {
        $this->mime_type = $mime_type;
        return $this;
    }

    public function getSize() : ?int
    {
        return $this->size;
    }

    public function setSize(?int $size) : static
    {
        $this->size = $size;
        return $this;
    }

    public function getCreatedBy() : ?int
    {
        return $this->created_by;
    }

    public function setCreatedBy(?int $created_by) : static
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at) : static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at) : static
    {
        $this->updated_at = $updated_at;
        return $this;
    }
}
