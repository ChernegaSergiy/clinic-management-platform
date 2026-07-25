<?php

namespace App\Core\Http;

use App\Core\Auth\MfaGuard;
use App\Core\Service\TranslationService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

class View
{
    private static ?Environment $twig = null;
    private static array $twigGlobals = [];
    private static ?TranslationService $translationService = null;

    public static function setTranslationService(TranslationService $service): void
    {
        self::$translationService = $service;
        // Ensure globals are loaded so locale detection can use system settings
        self::loadTwigGlobals();

        $preferredLocale = (self::$twigGlobals['system_locale'] ?? null) ?? self::detectBrowserLanguage() ?? 'uk';
        $rawAvailableLocales = array_keys(self::$translationService->getAvailableLocales());
        $finalLocale = 'uk';
        if (in_array($preferredLocale, $rawAvailableLocales)) {
            $finalLocale = $preferredLocale;
        }
        self::$translationService->setLocale($finalLocale);
    }

    public static function getTranslationService(): TranslationService
    {
        if (self::$translationService === null) {
            self::$translationService = new TranslationService();
            // If setTranslationService wasn't used, preserve old behavior
            self::loadTwigGlobals();
            $preferredLocale = (self::$twigGlobals['system_locale'] ?? null) ?? self::detectBrowserLanguage() ?? 'uk';
            $rawAvailableLocales = array_keys(self::$translationService->getAvailableLocales());
            $finalLocale = 'uk';
            if (in_array($preferredLocale, $rawAvailableLocales)) {
                $finalLocale = $preferredLocale;
            }
            self::$translationService->setLocale($finalLocale);
        }
        return self::$translationService;
    }

    private static function loadTwigGlobals(): void
    {
        if (!empty(self::$twigGlobals)) {
            return;
        }

        self::$twigGlobals = [
            'clinic_name' => 'Міська клінічна лікарня №1',
        ];

        try {
            $conn = \App\Kernel::$staticContainer->get(\Doctrine\Persistence\ManagerRegistry::class)->getConnection();
            $sql = "SELECT `key`, value FROM settings";
            $result = $conn->executeQuery($sql);
            while ($row = $result->fetchAssociative()) {
                self::$twigGlobals[$row['key']] = $row['value'];
            }
        } catch (\Exception $e) {
            // DB not available
        }
    }

    private static function detectBrowserLanguage(): ?string
    {
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (empty($acceptLang)) {
            return null;
        }

        $supportedLocales = array_keys(self::getTranslationService()->getAvailableLocales());

        // Parse Accept-Language header
        $languages = explode(',', $acceptLang);
        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = explode('-', $lang)[0]; // Get primary language code

            // Check if we support this language
            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }
        }

        return null;
    }

    private static function getTwig(): Environment
    {
        if (self::$twig === null) {
            self::loadTwigGlobals();

            // Initialize translation service
            $translationService = self::getTranslationService();

            $loader = new FilesystemLoader(__DIR__ . '/../../../templates');
            $loader->addPath(__DIR__ . '/../../../src/Module', 'modules');
            self::$twig = new Environment($loader, []);
            self::$twig->addGlobal('session', $_SESSION);

            // Add translation extension
            self::$twig->addExtension(new TranslationExtension(self::$translationService->getTranslator()));

            foreach (self::$twigGlobals as $key => $value) {
                self::$twig->addGlobal($key, $value);
            }
        }
        return self::$twig;
    }

    public static function render(string $template, array $data = []): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        if (MfaGuard::isRequired() && !str_starts_with($requestUri, '/user/mfa/')) {
            header('Location: /user/mfa/required');
            exit();
        }

        echo self::getTwig()->render($template, $data);
    }

    public static function renderToString(string $template, array $data = []): string
    {
        return self::getTwig()->render($template, $data);
    }

    public static function clearCache(): void
    {
        self::$twig = null;
        self::$twigGlobals = [];
        self::$translationService = null;
    }
}
