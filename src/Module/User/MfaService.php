<?php

namespace App\Module\User;

use App\Database\Database;
use OTPHP\TOTP;
use OTPHP\HOTP;
use App\Core\Service\QrCodeGenerator;
use PDO;

class MfaService
{
    private PDO $db;
    private QrCodeGenerator $qrCodeGenerator;
    private string $issuerName;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->qrCodeGenerator = new QrCodeGenerator();
        $this->issuerName = $_ENV['APP_NAME'] ?? 'Clinic';
    }

    public function generateSecret(): string
    {
        $totp = TOTP::create();
        return $totp->getSecret();
    }

    public function generateHotpSecret(): string
    {
        $hotp = HOTP::create();
        return $hotp->getSecret();
    }

    public function generateQRCode(string $secret, string $userEmail): string
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($userEmail);
        $totp->setIssuer($this->issuerName);

        $otpauthUri = $totp->getProvisioningUri();
        return $this->qrCodeGenerator->generateQrCodeAsBase64($otpauthUri);
    }

    public function generateHotpQRCode(string $secret, string $userEmail, int $counter = 0): string
    {
        $hotp = HOTP::create($secret);
        $hotp->setLabel($userEmail);
        $hotp->setIssuer($this->issuerName);
        $hotp->setCounter($counter);

        $otpauthUri = $hotp->getProvisioningUri();
        return $this->qrCodeGenerator->generateQrCodeAsBase64($otpauthUri);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $totp = TOTP::create($secret);
        return $totp->verify($code);
    }

    public function verifyHotpCode(string $secret, string $code, int $counter, int $window = 10): bool
    {
        $hotp = HOTP::create($secret);
        return $hotp->verify($code, $counter, $window);
    }

    public function verifyHotpCodeWithCounter(string $secret, string $code, int $currentCounter, int $lastCounter = 0, int $window = 10): ?int
    {
        $hotp = HOTP::create($secret);

        for ($i = $currentCounter; $i < $currentCounter + $window; $i++) {
            if ($i <= $lastCounter) {
                continue;
            }

            if ($hotp->verify($code, $i, 0)) {
                return $i;
            }
        }

        return null;
    }

    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->generateRandomCode(8);
        }
        return $codes;
    }

    private function generateRandomCode(int $length): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $code .= '-';
            }
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }

    public function enableMfaForUser(int $userId, string $secret, array $backupCodes, string $mfaType = 'totp'): bool
    {
        $mfaType = in_array($mfaType, ['totp', 'hotp', 'sms', 'email'], true) ? $mfaType : 'totp';

        $stmt = $this->db->prepare("
            UPDATE users
            SET mfa_enabled = 1,
                mfa_type = :mfa_type,
                mfa_secret = :secret,
                mfa_backup_codes = :backup_codes,
                mfa_verified_at = NOW(),
                mfa_pending = 0,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $userId,
            'mfa_type' => $mfaType,
            'secret' => $secret,
            'backup_codes' => json_encode($backupCodes),
        ]);
    }

    public function enableHotpForUser(int $userId, string $secret, array $backupCodes, int $counter = 0): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET mfa_enabled = 1,
                mfa_type = 'hotp',
                mfa_secret = :secret,
                mfa_backup_codes = :backup_codes,
                mfa_counter = :counter,
                mfa_last_counter = :last_counter,
                mfa_verified_at = NOW(),
                mfa_pending = 0,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $userId,
            'secret' => $secret,
            'backup_codes' => json_encode($backupCodes),
            'counter' => $counter,
            'last_counter' => $counter > 0 ? $counter - 1 : 0,
        ]);
    }

    public function disableMfaForUser(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET mfa_enabled = 0,
                mfa_type = 'totp',
                mfa_secret = NULL,
                mfa_backup_codes = NULL,
                mfa_counter = 0,
                mfa_verified_at = NULL,
                mfa_pending = 0,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute(['id' => $userId]);
    }

    public function verifyUserMfa(int $userId, string $code): bool
    {
        $stmt = $this->db->prepare("SELECT mfa_secret, mfa_type, mfa_counter, mfa_last_counter, mfa_backup_codes FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || empty($user['mfa_secret'])) {
            return false;
        }

        $mfaType = $user['mfa_type'] ?? 'totp';
        $secret = $user['mfa_secret'];
        $counter = $user['mfa_counter'] ?? 0;
        $lastCounter = $user['mfa_last_counter'] ?? 0;

        if ($mfaType === 'hotp') {
            $verifiedCounter = $this->verifyHotpCodeWithCounter($secret, $code, $counter, $lastCounter);

            if ($verifiedCounter !== null) {
                $this->updateHotpCounter($userId, $verifiedCounter);
                return true;
            }
        } else {
            if ($this->verifyCode($secret, $code)) {
                return true;
            }
        }

        $backupCodes = json_decode($user['mfa_backup_codes'] ?? '[]', true);
        if (is_array($backupCodes) && in_array(strtoupper($code), $backupCodes, true)) {
            $this->removeUsedBackupCode($userId, $code);
            return true;
        }

        return false;
    }

    private function updateHotpCounter(int $userId, int $counter): void
    {
        $stmt = $this->db->prepare("UPDATE users SET mfa_counter = :next_counter, mfa_last_counter = :last_counter WHERE id = :id");
        $stmt->execute(['id' => $userId, 'next_counter' => $counter + 1, 'last_counter' => $counter]);
    }

    private function removeUsedBackupCode(int $userId, string $code): void
    {
        $stmt = $this->db->prepare("SELECT mfa_backup_codes FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return;
        }

        $backupCodes = json_decode($user['mfa_backup_codes'] ?? '[]', true);
        $backupCodes = array_filter($backupCodes, fn($c) => strtoupper($c) !== strtoupper($code));

        $stmt = $this->db->prepare("UPDATE users SET mfa_backup_codes = :codes WHERE id = :id");
        $stmt->execute(['id' => $userId, 'codes' => json_encode(array_values($backupCodes))]);
    }

    public function isMfaEnabled(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT mfa_enabled FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        return $user && $user['mfa_enabled'] == 1;
    }

    public function isMfaPending(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT mfa_pending FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        return $user && $user['mfa_pending'] == 1;
    }

    public function setMfaPending(int $userId, bool $pending): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET mfa_pending = :pending WHERE id = :id");
        return $stmt->execute(['id' => $userId, 'pending' => $pending ? 1 : 0]);
    }

    public function getUserMfaStatus(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT mfa_enabled, mfa_type, mfa_verified_at, mfa_pending
            FROM users WHERE id = :id
        ");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        return [
            'enabled' => (bool)($user['mfa_enabled'] ?? false),
            'type' => $user['mfa_type'] ?? null,
            'verified_at' => $user['mfa_verified_at'] ?? null,
            'pending' => (bool)($user['mfa_pending'] ?? false),
        ];
    }
}
