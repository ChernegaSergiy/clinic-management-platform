<?php

namespace App\Module\User\Repository;

use App\Database;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Finds a user by OAuth provider and ID, or links an existing user by email.
     * Does NOT create a new user if no match is found.
     *
     * @param string $provider
     * @param \League\OAuth2\Client\Provider\ResourceOwnerInterface $owner
     * @return array|null
     */
    public function findOrLinkFromOAuth(string $provider, \League\OAuth2\Client\Provider\ResourceOwnerInterface $owner): ?array
    {
        // 1. Check if user with provider ID already exists
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE provider = :provider AND provider_id = :provider_id");
        $stmt->execute([':provider' => $provider, ':provider_id' => $owner->getId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user;
        }

        // 2. Check if user with email exists to link account
        $user = $this->findByEmail($owner->getEmail());
        if ($user) {
            $stmt = $this->pdo->prepare("UPDATE users SET provider = :provider, provider_id = :provider_id WHERE id = :id");
            $stmt->execute([
                ':provider' => $provider,
                ':provider_id' => $owner->getId(),
                ':id' => $user['id']
            ]);
            return $this->findById($user['id']);
        }

        // No matching user found, and we don't create new ones automatically via this method
        return null;
    }

    public function findAll(string $searchTerm = ''): array
    {
        $sql = "SELECT id, first_name, last_name, email, role_id FROM users";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE first_name LIKE :term OR last_name LIKE :term OR email LIKE :term"
                . " OR CONCAT(first_name, ' ', last_name) LIKE :term";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY last_name, first_name";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findAllActive(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, first_name, last_name, email, role_id, CONCAT(first_name, ' ', last_name) AS full_name
            FROM users 
            WHERE role_id IS NOT NULL
            ORDER BY last_name, first_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllDoctors(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, first_name, last_name, email, role_id, CONCAT(first_name, ' ', last_name) AS full_name
            FROM users 
            WHERE role_id = (SELECT id FROM roles WHERE name = 'doctor' LIMIT 1)
            ORDER BY last_name, first_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                id, 
                first_name, 
                last_name, 
                email, 
                role_id, 
                created_at, 
                updated_at,
                provider,
                provider_id
            FROM users 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        if ($result === false) {
            return null;
        }

        // Cast timestamps to DateTime objects for safer usage in views.
        if (!empty($result['created_at'])) {
            $result['created_at'] = new \DateTimeImmutable($result['created_at']);
        }
        if (!empty($result['updated_at'])) {
            $result['updated_at'] = new \DateTimeImmutable($result['updated_at']);
        }

        return $result;
    }

    public function findByEmailExcludingId(string $email, int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function save(array $data): bool
    {
        $username = $data['username'] ?? $data['email'];

        $sql = "INSERT INTO users (first_name, last_name, email, username, password_hash, role_id) 
                VALUES (:first_name, :last_name, :email, :username, :password_hash, :role_id)";

        $stmt = $this->pdo->prepare($sql);

        $passwordHash = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;

        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':username' => $username,
            ':password_hash' => $passwordHash,
            ':role_id' => $data['role_id'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE users SET 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    email = :email, 
                    role_id = :role_id";

        $params = [
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':role_id' => $data['role_id'],
        ];

        if (isset($data['password']) && !empty($data['password'])) { // Only update password if provided
            $sql .= ", password_hash = :password_hash";
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countUsers(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function ensureDefaultAdminExists(): void
    {
        if ($this->countUsers() > 0) {
            return;
        }

        $email = getenv('ADMIN_EMAIL') ?: 'admin@clinic.ua';
        $password = getenv('ADMIN_PASSWORD') ?: 'password';

        $sql = "INSERT INTO users (first_name, last_name, email, username, password_hash, role_id) 
                VALUES (:first_name, :last_name, :email, :username, :password_hash, :role_id)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':first_name' => getenv('ADMIN_FIRST_NAME') ?: 'Адмін',
            ':last_name' => getenv('ADMIN_LAST_NAME') ?: 'Адміненко',
            ':email' => $email,
            ':username' => 'admin',
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role_id' => 1,
        ]);
    }

    public function unlinkProvider(int $userId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET provider = NULL, provider_id = NULL WHERE id = :id");
        return $stmt->execute([':id' => $userId]);
    }
}
