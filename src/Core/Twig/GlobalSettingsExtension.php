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

namespace App\Core\Twig;

use App\Core\Repository\SettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class GlobalSettingsExtension extends AbstractExtension implements GlobalsInterface
{
    private SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function getGlobals() : array
    {
        $globals = [
            'clinic_name' => 'Міська клінічна лікарня №1', // Fallback default
        ];

        try {
            $settings = $this->settingsRepository->getAll();
            $globals = array_merge($globals, $settings);
        } catch (\Exception $e) {
            // DB not available, fallback to defaults
        }

        // Inject raw session to templates, mirroring the old View behavior
        $globals['session'] = $_SESSION ?? [];

        return $globals;
    }
}
