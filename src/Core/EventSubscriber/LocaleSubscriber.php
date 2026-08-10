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

namespace App\Core\EventSubscriber;

use App\Core\Repository\SettingsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function onKernelRequest(RequestEvent $event) : void
    {
        $request = $event->getRequest();

        $systemLocale = null;
        try {
            $systemLocale = $this->settingsRepository->get('system_locale');
        } catch (\Exception $e) {
            // DB not available, fallback to browser
        }

        $preferredLocale = $systemLocale ?? $this->detectBrowserLanguage($request) ?? 'uk';

        $availableLocales = $this->getAvailableLocales();
        $finalLocale = 'uk';

        if (in_array($preferredLocale, $availableLocales, true)) {
            $finalLocale = $preferredLocale;
        }

        $request->setLocale($finalLocale);
    }

    private function detectBrowserLanguage(Request $request) : ?string
    {
        $acceptLang = $request->headers->get('Accept-Language', '');
        if (empty($acceptLang)) {
            return null;
        }

        $supportedLocales = $this->getAvailableLocales();

        $languages = explode(',', $acceptLang);
        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = explode('-', $lang)[0];

            if (in_array($lang, $supportedLocales, true)) {
                return $lang;
            }
        }

        return null;
    }

    private function getAvailableLocales() : array
    {
        $locales = [];
        $bundlesDir = __DIR__ . '/../../../Bundles';

        if (!is_dir($bundlesDir)) {
            return ['uk', 'en'];
        }

        $bundles = scandir($bundlesDir);
        foreach ($bundles as $bundle) {
            $translationsDir = $bundlesDir . '/' . $bundle . '/translations';
            if (!is_dir($translationsDir)) {
                continue;
            }

            $files = scandir($translationsDir);
            foreach ($files as $file) {
                if (str_starts_with($file, 'messages.') && str_ends_with($file, '.yaml')) {
                    $locale = substr($file, 9, -5);
                    $locales[$locale] = true;
                }
            }
        }

        return array_keys($locales);
    }

    public static function getSubscribedEvents() : array
    {
        return [
            // Priority 20 to ensure locale is set early
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
