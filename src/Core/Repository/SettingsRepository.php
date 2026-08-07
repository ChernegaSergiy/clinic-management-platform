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

namespace App\Core\Repository;

use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;

class SettingsRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function get(string $key, mixed $default = null) : mixed
    {
        $setting = $this->em->find(Settings::class, $key);

        if (null === $setting) {
            return $default;
        }

        $value = $setting->getValue();

        if (null === $value || '' === $value) {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value) : bool
    {
        $setting = $this->em->find(Settings::class, $key);

        if (null === $setting) {
            $setting = new Settings();
            $setting->setKey($key);
            $setting->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($setting);
        }

        $val = is_array($value) || is_object($value) ? json_encode($value) : $value;
        $setting->setValue($val);
        $setting->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();
        return true;
    }

    public function delete(string $key) : bool
    {
        $setting = $this->em->find(Settings::class, $key);

        if (null === $setting) {
            return false;
        }

        $this->em->remove($setting);
        $this->em->flush();
        return true;
    }

    public function getMfaPolicy() : string
    {
        return $this->get('mfa_policy', 'optional');
    }

    public function setMfaPolicy(string $policy) : bool
    {
        return $this->set('mfa_policy', $policy);
    }

    public function getMfaForceRoles() : array
    {
        $value = $this->get('mfa_force_roles', null);
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setMfaForceRoles(array $roleIds) : bool
    {
        return $this->set('mfa_force_roles', $roleIds);
    }
}
