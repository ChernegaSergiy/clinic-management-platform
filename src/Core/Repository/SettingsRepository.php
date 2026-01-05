<?php

namespace App\Core\Repository;

use App\Database\Database;
use PDO;

class SettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stmt = $this->pdo->prepare("SELECT value FROM settings WHERE `key` = :key");
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch();

        if ($result === false) {
            return $default;
        }

        $value = $result['value'];

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO settings (`key`, value, updated_at)
            VALUES (:key, :value, NOW())
            ON DUPLICATE KEY UPDATE value = :value2, updated_at = NOW()
        ");

        return $stmt->execute([
            ':key' => $key,
            ':value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
            ':value2' => is_array($value) || is_object($value) ? json_encode($value) : $value,
        ]);
    }

    public function delete(string $key): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM settings WHERE `key` = :key");
        return $stmt->execute([':key' => $key]);
    }

    public function getMfaPolicy(): string
    {
        return $this->get('mfa_policy', 'optional');
    }

    public function setMfaPolicy(string $policy): bool
    {
        return $this->set('mfa_policy', $policy);
    }

    public function getMfaForceRoles(): array
    {
        $value = $this->get('mfa_force_roles', null);
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setMfaForceRoles(array $roleIds): bool
    {
        return $this->set('mfa_force_roles', $roleIds);
    }
}
