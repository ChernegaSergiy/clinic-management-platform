<?php

namespace App\Core\Twig;

use App\Core\Repository\SettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    private static ?array $cache = null;
    private SettingsRepository $settingsRepository;

    public function __construct()
    {
        $this->settingsRepository = new SettingsRepository();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('setting', [$this, 'getSetting'], ['is_safe' => ['html']]),
            new TwigFunction('clinic_name', [$this, 'getClinicName'], ['is_safe' => ['html']]),
        ];
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::$cache = [];
        }

        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $this->settingsRepository->get($key, $default);
        }

        return self::$cache[$key];
    }

    public function getClinicName(): string
    {
        return $this->getSetting('clinic_name', 'Міська клінічна лікарня №1');
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
