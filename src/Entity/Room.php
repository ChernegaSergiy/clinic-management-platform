<?php

namespace App\Entity;

use App\Bundles\RoomBundle\Repository\RoomRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ORM\Table(name: 'rooms')]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $capacity = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $equipment = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $is_available = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : static
    {
        $this->name = $name;
        return $this;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function setType(string $type) : static
    {
        $this->type = $type;
        return $this;
    }

    public function getCapacity() : int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity) : static
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function getLocation() : ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location) : static
    {
        $this->location = $location;
        return $this;
    }

    public function getEquipment() : ?string
    {
        return $this->equipment;
    }

    public function setEquipment(?string $equipment) : static
    {
        $this->equipment = $equipment;
        return $this;
    }

    public function isAvailable() : bool
    {
        return $this->is_available;
    }

    public function setIsAvailable(bool $isAvailable) : static
    {
        $this->is_available = $isAvailable;
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

    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt) : static
    {
        $this->updated_at = $updatedAt;
        return $this;
    }
}
