<?php

namespace App\Core\Http;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    private static ?Environment $twig = null;

    private static function getTwig(): Environment
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../../../templates');
            $loader->addPath(__DIR__ . '/../../../src/Module', 'modules');
            self::$twig = new Environment($loader, [
            ]);
            self::$twig->addGlobal('session', $_SESSION);
        }
        return self::$twig;
    }

    public static function render(string $template, array $data = []): void
    {
        echo self::getTwig()->render($template, $data);
    }

    public static function renderToString(string $template, array $data = []): string
    {
        return self::getTwig()->render($template, $data);
    }
}
