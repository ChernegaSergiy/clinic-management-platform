<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Auth;

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
