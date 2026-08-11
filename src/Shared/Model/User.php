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

namespace App\Shared\Model;

class User
{
    private array $data;
    private array $permissions;

    public function __construct(array $data, array $permissions)
    {
        $this->data = $data;
        $this->permissions = $permissions;
    }

    public function getId() : ?int
    {
        return $this->data['id'] ?? null;
    }

    public function getRole() : string
    {
        return $this->data['role_name'] ?? '';
    }

    public function isAdmin() : bool
    {
        return 'admin' === $this->getRole();
    }

    public function hasPermission(string $permission) : bool
    {
        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }
}
