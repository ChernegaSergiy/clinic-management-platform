<?php

namespace App\Core\Auth;

enum AuthStep : string
{
    case CREDENTIALS = 'credentials';
    case MFA_SETUP = 'mfa_setup';
    case MFA_VERIFY = 'mfa_verify';
    case AUTHENTICATED = 'authenticated';

    public static function current() : self
    {
        if (isset($_SESSION['user'])) {
            return self::AUTHENTICATED;
        }

        if (isset($_SESSION['mfa_required']) && true === $_SESSION['mfa_required']) {
            return self::MFA_SETUP;
        }

        if (isset($_SESSION['mfa_pending_user_id'])) {
            return self::MFA_VERIFY;
        }

        return self::CREDENTIALS;
    }

    public function isAuthorized() : bool
    {
        return self::AUTHENTICATED === $this;
    }

    public function requiresMfaSetup() : bool
    {
        return self::MFA_SETUP === $this;
    }

    public function requiresMfaVerify() : bool
    {
        return self::MFA_VERIFY === $this;
    }
}
