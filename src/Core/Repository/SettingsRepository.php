<?php

namespace App\Core\Repository;

use Doctrine\Persistence\ManagerRegistry;

class SettingsRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $conn = $this->registry->getConnection();
        $sql = "SELECT value FROM settings WHERE `key` = :key";
        $result = $conn->fetchAssociative($sql, ['key' => $key]);

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
        $conn = $this->registry->getConnection();
        $sql = "
            INSERT INTO settings (`key`, value, updated_at)
            VALUES (:key, :value, NOW())
            ON DUPLICATE KEY UPDATE value = :value2, updated_at = NOW()
        ";

        $val = is_array($value) || is_object($value) ? json_encode($value) : $value;
        return $conn->executeStatement($sql, [
            'key' => $key,
            'value' => $val,
            'value2' => $val,
        ]) > 0;
    }

    public function delete(string $key): bool
    {
        $conn = $this->registry->getConnection();
        $sql = "DELETE FROM settings WHERE `key` = :key";
        return $conn->executeStatement($sql, ['key' => $key]) > 0;
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
