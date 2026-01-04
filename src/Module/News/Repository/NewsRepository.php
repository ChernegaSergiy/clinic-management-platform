<?php

namespace App\Module\News\Repository;

use Api\Database\Database;
use PDO;

class NewsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM news_articles ORDER BY published_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM news_articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO news_articles (title, meta, content, published_at, author_id, is_published) VALUES (:title, :meta, :content, :published_at, :author_id, :is_published)");
        $stmt->execute([
            'title' => $data['title'],
            'meta' => $data['meta'],
            'content' => $data['content'],
            'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
            'author_id' => $data['author_id'] ?? null,
            'is_published' => $data['is_published'] ?? true,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("UPDATE news_articles SET title = :title, meta = :meta, content = :content, published_at = :published_at, author_id = :author_id, is_published = :is_published, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'meta' => $data['meta'],
            'content' => $data['content'],
            'published_at' => $data['published_at'],
            'author_id' => $data['author_id'],
            'is_published' => $data['is_published'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM news_articles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
