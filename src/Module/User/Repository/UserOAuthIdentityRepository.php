<?php

namespace App\Module\User\Repository;

use App\Database;
use PDO;

class UserOAuthIdentityRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByProviderAndProviderId(string $provider, string $providerId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_oauth_identities WHERE provider = :provider AND provider_id = :provider_id");
        $stmt->execute([':provider' => $provider, ':provider_id' => $providerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function findByUserIdAndProvider(int $userId, string $provider): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_oauth_identities WHERE user_id = :user_id AND provider = :provider");
        $stmt->execute([':user_id' => $userId, ':provider' => $provider]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function create(int $userId, string $provider, string $providerId): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO user_oauth_identities (user_id, provider, provider_id, created_at, updated_at) VALUES (:user_id, :provider, :provider_id, NOW(), NOW())");
        return $stmt->execute([
            ':user_id' => $userId,
            ':provider' => $provider,
            ':provider_id' => $providerId,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_oauth_identities WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function deleteByUserIdAndProvider(int $userId, string $provider): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_oauth_identities WHERE user_id = :user_id AND provider = :provider");
        return $stmt->execute([':user_id' => $userId, ':provider' => $provider]);
    }

    public function findAllByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_oauth_identities WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
