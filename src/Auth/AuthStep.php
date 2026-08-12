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

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

enum AuthStep : string
{
    case CREDENTIALS = 'credentials';
    case MFA_SETUP = 'mfa_setup';
    case MFA_VERIFY = 'mfa_verify';
    case AUTHENTICATED = 'authenticated';

    public static function current(?TokenStorageInterface $tokenStorage = null, ?SessionInterface $session = null) : self
    {
        if ($tokenStorage && $tokenStorage->getToken()?->getUser()) {
            return self::AUTHENTICATED;
        }

        if (true === $session?->get('mfa_required')) {
            return self::MFA_SETUP;
        }

        if ($session?->has('mfa_pending_user_id')) {
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
