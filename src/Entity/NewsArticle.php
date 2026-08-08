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

use App\Bundles\NewsBundle\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
#[ORM\Table(name: 'news_articles')]
class NewsArticle
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $title = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $meta = null;
    #[ORM\Column(type: Types::TEXT)] private ?string $content = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $published_at = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $author_id = null;
    #[ORM\Column(type: Types::BOOLEAN)] private bool $is_published = false;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }
    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
    public function getTitle() : ?string
    {
        return $this->title;
    }
    public function setTitle(?string $title) : self
    {
        $this->title = $title;
        return $this;
    }
    public function getMeta() : ?string
    {
        return $this->meta;
    }
    public function setMeta(?string $meta) : self
    {
        $this->meta = $meta;
        return $this;
    }
    public function getContent() : ?string
    {
        return $this->content;
    }
    public function setContent(?string $content) : self
    {
        $this->content = $content;
        return $this;
    }
    public function getPublishedAt() : ?\DateTimeInterface
    {
        return $this->published_at;
    }
    public function setPublishedAt(?\DateTimeInterface $published_at) : self
    {
        $this->published_at = $published_at;
        return $this;
    }
    public function getAuthorId() : ?int
    {
        return $this->author_id;
    }
    public function setAuthorId(?int $author_id) : self
    {
        $this->author_id = $author_id;
        return $this;
    }
    public function isIsPublished() : bool
    {
        return $this->is_published;
    }
    public function setIsPublished(bool $is_published) : self
    {
        $this->is_published = $is_published;
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
}
