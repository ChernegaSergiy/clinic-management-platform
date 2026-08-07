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

namespace App\Core\Auth;

use App\Core\Exception\ExitException;
use App\Core\Model\User;

class Gate
{
    private PermissionRegistry $permissionRegistry;
    private PolicyRegistry $policyRegistry;

    public function __construct(PermissionRegistry $permissionRegistry, PolicyRegistry $policyRegistry)
    {
        $this->permissionRegistry = $permissionRegistry;
        $this->policyRegistry = $policyRegistry;
    }

    public function getUser() : ?User
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $permissions = $_SESSION['user']['permissions'] ?? $this->permissionRegistry->getRolePermissions($_SESSION['user']['role_name']);
        return new User($_SESSION['user'], $permissions);
    }

    public function authorize(string $ability, $context = []) : void
    {
        $user = $this->getUser();

        if (!$user) {
            throw new ExitException("Доступ заборонено (не автентифіковано)", 403);
        }

        if ($this->allows($ability, $context)) {
            return;
        }

        throw new ExitException("Доступ заборонено", 403);
    }

    public function allows(string $ability, $context = []) : bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $parts = explode('.', $ability, 2);

        if (2 === count($parts)) {
            $resourceKey = $parts[0];
            $verb = $parts[1];

            if ($policy = $this->policyRegistry->get($resourceKey)) {
                if (method_exists($policy, $verb)) {
                    return $policy->$verb($user, $context);
                }
            }
        }

        if ($user->hasPermission($ability)) {
            return true;
        }

        return false;
    }
}
