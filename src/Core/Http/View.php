<?php

namespace App\Core\Http;

use App\Core\Auth\MfaGuard;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    private static ?Environment $twig = null;
    private static array $twigGlobals = [];

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

    private static function getTwig(): Environment
    {
        if (self::$twig === null) {
            self::loadTwigGlobals();

            $loader = new FilesystemLoader(__DIR__ . '/../../../templates');
            $loader->addPath(__DIR__ . '/../../../src/Module', 'modules');
            self::$twig = new Environment($loader, []);
            self::$twig->addGlobal('session', $_SESSION);

            foreach (self::$twigGlobals as $key => $value) {
                self::$twig->addGlobal($key, $value);
            }
        }
        return self::$twig;
    }

    public static function render(string $template, array $data = []): void
    {
        if (MfaGuard::isRequired() && strpos($template, 'mfa_required') === false && strpos($template, 'hotp_required') === false && strpos($template, 'mfa_setup') === false && strpos($template, 'mfa_verify') === false) {
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
