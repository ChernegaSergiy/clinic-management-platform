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

namespace App\Infrastructure\EventSubscriber;

use App\Shared\Repository\SettingsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SettingsRepository $settingsRepository,
        private array $supportedLocales = ['uk', 'en'],
    ) {
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

        if ($systemLocale && in_array($systemLocale, $this->supportedLocales, true)) {
            $finalLocale = $systemLocale;
        } else {
            $finalLocale = $request->getPreferredLanguage($this->supportedLocales) ?: 'uk';
        }

        $request->setLocale($finalLocale);
    }

    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
