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

namespace App\Bundles\NewsBundle\Repository;

use App\Entity\NewsArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsArticle::class);
    }

    public function findAll() : array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM news_articles ORDER BY published_at DESC";
        return $conn->fetchAllAssociative($sql);
    }

    public function findById(int $id) : ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM news_articles WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $id]);
        return $result ?: null;
    }

    public function create(array $data) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO news_articles (title, meta, content, published_at, author_id, is_published) 
                VALUES (:title, :meta, :content, :published_at, :author_id, :is_published)";
        $conn->executeStatement($sql, [
            'title' => $data['title'],
            'meta' => $data['meta'],
            'content' => $data['content'],
            'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
            'author_id' => $data['author_id'] ?? null,
            'is_published' => (int)($data['is_published'] ?? true),
        ]);
        return (int)$conn->lastInsertId();
    }

    public function update(int $id, array $data) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE news_articles SET 
                    title = :title, 
                    meta = :meta, 
                    content = :content, 
                    published_at = :published_at, 
                    author_id = :author_id, 
                    is_published = :is_published, 
                    updated_at = NOW() 
                WHERE id = :id";
        return $conn->executeStatement($sql, [
            'id' => $id,
            'title' => $data['title'],
            'meta' => $data['meta'],
            'content' => $data['content'],
            'published_at' => $data['published_at'],
            'author_id' => $data['author_id'],
            'is_published' => (int)$data['is_published'],
        ]) > 0;
    }

    public function delete(int $id) : bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "DELETE FROM news_articles WHERE id = :id";
        return $conn->executeStatement($sql, ['id' => $id]) > 0;
    }
}
