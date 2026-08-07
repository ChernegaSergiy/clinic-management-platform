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

class PolicyRegistry
{
    private array $policies = [];

    public function register(string $resourceKey, string $policyClass) : void
    {
        if (!class_exists($policyClass)) {
            return;
        }

        $this->policies[$resourceKey] = $policyClass;
    }

    public function get(string $resourceKey) : ?object
    {
        if (!isset($this->policies[$resourceKey])) {
            return null;
        }

        $policyClass = $this->policies[$resourceKey];
        return new $policyClass();
    }
}
