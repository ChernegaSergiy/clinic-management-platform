<?php

namespace App\Entity;

use App\Module\User\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password_hash = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $last_name = null;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Role $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profile_photo_path = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $mfa_enabled = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mfa_type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $mfa_verified_at = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $mfa_pending = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getUsername() : ?string
    {
        return $this->username;
    }

    public function setUsername(string $username) : static
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function setEmail(string $email) : static
    {
        $this->email = $email;
        return $this;
    }

    public function getPasswordHash() : ?string
    {
        return $this->password_hash;
    }

    public function setPasswordHash(?string $passwordHash) : static
    {
        $this->password_hash = $passwordHash;
        return $this;
    }

    public function getFirstName() : ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $firstName) : static
    {
        $this->first_name = $firstName;
        return $this;
    }

    public function getLastName() : ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $lastName) : static
    {
        $this->last_name = $lastName;
        return $this;
    }

    public function getRole() : ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role) : static
    {
        $this->role = $role;
        return $this;
    }

    public function getProfilePhotoPath() : ?string
    {
        return $this->profile_photo_path;
    }

    public function setProfilePhotoPath(?string $profilePhotoPath) : static
    {
        $this->profile_photo_path = $profilePhotoPath;
        return $this;
    }

    public function isMfaEnabled() : bool
    {
        return $this->mfa_enabled;
    }

    public function setMfaEnabled(bool $mfaEnabled) : static
    {
        $this->mfa_enabled = $mfaEnabled;
        return $this;
    }

    public function getMfaType() : ?string
    {
        return $this->mfa_type;
    }

    public function setMfaType(?string $mfaType) : static
    {
        $this->mfa_type = $mfaType;
        return $this;
    }

    public function getMfaVerifiedAt() : ?\DateTimeInterface
    {
        return $this->mfa_verified_at;
    }

    public function setMfaVerifiedAt(?\DateTimeInterface $mfaVerifiedAt) : static
    {
        $this->mfa_verified_at = $mfaVerifiedAt;
        return $this;
    }

    public function isMfaPending() : bool
    {
        return $this->mfa_pending;
    }

    public function setMfaPending(bool $mfaPending) : static
    {
        $this->mfa_pending = $mfaPending;
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
