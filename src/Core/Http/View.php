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

namespace App\Core\Http;

use App\Core\Auth\MfaGuard;
use App\Core\Service\TranslationService;
use Doctrine\Persistence\ManagerRegistry;
use Twig\Environment;

class View
{
    private Environment $twig;
    private TranslationService $translationService;
    private ManagerRegistry $registry;
    private MfaGuard $mfaGuard;

    public function __construct(Environment $twig, TranslationService $translationService, ManagerRegistry $registry, MfaGuard $mfaGuard)
    {
        $this->twig = $twig;
        $this->translationService = $translationService;
        $this->registry = $registry;
        $this->mfaGuard = $mfaGuard;
    }


    public function render(string $template, array $data = []) : void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        if ($this->mfaGuard->isRequired() && !str_starts_with($requestUri, '/user/mfa/')) {
            header('Location: /user/mfa/required');
            exit();
        }

        echo $this->twig->render($template, $data);
    }

    public function renderToString(string $template, array $data = []) : string
    {
        return $this->twig->render($template, $data);
    }

    public function getTranslationService() : TranslationService
    {
        return $this->translationService;
    }
}
