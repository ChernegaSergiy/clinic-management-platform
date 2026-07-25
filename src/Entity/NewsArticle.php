<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Module\News\Repository\NewsRepository;

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
}
