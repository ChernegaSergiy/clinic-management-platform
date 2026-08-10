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

use App\Core\Repository\AttachmentVersionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttachmentVersionRepository::class)]
#[ORM\Table(name: 'attachment_versions')]
class AttachmentVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(name: 'attachment_id', referencedColumnName: 'id', nullable: false)]
    private ?Attachment $attachment = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $version_number = null;

    #[ORM\Column(length: 500)]
    private ?string $filepath = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $size = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

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

    public function getVersionNumber() : ?int
    {
        return $this->version_number;
    }

    public function setVersionNumber(int $version_number) : static
    {
        $this->version_number = $version_number;
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

    public function getFilename() : ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename) : static
    {
        $this->filename = $filename;
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
}
