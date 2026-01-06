<?php

namespace App\Core\Auth;

enum AuthStep: string
{
    case CREDENTIALS = 'credentials';
    case MFA_SETUP = 'mfa_setup';
    case MFA_VERIFY = 'mfa_verify';
    case AUTHENTICATED = 'authenticated';

    public static function current(): self
    {
        if (isset($_SESSION['user'])) {
            return self::AUTHENTICATED;
        }

        if (isset($_SESSION['mfa_required']) && $_SESSION['mfa_required'] === true) {
            return self::MFA_SETUP;
        }

        if (isset($_SESSION['mfa_pending_user_id'])) {
            return self::MFA_VERIFY;
        }

        return self::CREDENTIALS;
    }

    public function isAuthorized(): bool
    {
        return $this === self::AUTHENTICATED;
    }

    public function requiresMfaSetup(): bool
    {
        return $this === self::MFA_SETUP;
    }

    public function requiresMfaVerify(): bool
    {
        return $this === self::MFA_VERIFY;
    }
}
