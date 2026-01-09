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

    private static function loadTwigGlobals(): void
    {
        if (!empty(self::$twigGlobals)) {
            return;
        }

        self::$twigGlobals = [
            'clinic_name' => 'Міська клінічна лікарня №1',
        ];

        try {
            $db = \App\Database\Database::getInstance();
            $stmt = $db->query("SELECT `key`, value FROM settings");
            while ($row = $stmt->fetch()) {
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

        // Parse Accept-Language header
        $languages = explode(',', $acceptLang);
        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = explode('-', $lang)[0]; // Get primary language code

            // Check if we support this language
            if (in_array($lang, ['uk', 'en'])) {
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
            if (self::$translationService === null) {
                self::$translationService = new TranslationService();

                // Set locale from session, browser, or default
                $locale = $_SESSION['locale'] ?? self::detectBrowserLanguage() ?? 'uk';
                self::$translationService->setLocale($locale);
            }

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
    }
}
