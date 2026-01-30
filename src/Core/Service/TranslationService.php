<?php

namespace App\Core\Service;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Intl\Locales;

class TranslationService
{
    private TranslatorInterface $translator;
    private string $currentLocale = 'uk';
    private array $availableLocales = []; // Cache for available locales

    public function __construct()
    {
        $this->translator = new Translator($this->currentLocale);
        $this->translator->addLoader('yaml', new YamlFileLoader());

        $this->loadTranslations();
        $this->availableLocales = $this->scanTranslationDirectoriesForLocales();
    }

    private function loadTranslations(): void
    {
        $baseDir = __DIR__ . '/../../';

        // Load global translations
        $globalTranslationsDir = $baseDir . 'translations';
        if (is_dir($globalTranslationsDir)) {
            $this->loadFromDirectory($globalTranslationsDir);
        }

        // Load module translations
        $modulesDir = $baseDir . 'Module';
        if (is_dir($modulesDir)) {
            $modules = scandir($modulesDir);
            foreach ($modules as $module) {
                if ($module === '.' || $module === '..') {
                    continue;
                }

                $moduleTranslationsDir = $modulesDir . '/' . $module . '/translations';
                if (is_dir($moduleTranslationsDir)) {
                    $this->loadFromDirectory($moduleTranslationsDir);
                }
            }
        }
    }

    private function loadFromDirectory(string $directory): void
    {
        $files = scandir($directory);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'yaml') {
                $locale = pathinfo($file, PATHINFO_FILENAME);
                if (strpos($locale, 'messages.') === 0) {
                    $locale = substr($locale, 9); // Remove 'messages.' prefix
                    $this->translator->addResource('yaml', $directory . '/' . $file, $locale);
                }
            }
        }
    }

    private function scanTranslationDirectoriesForLocales(): array
    {
        $foundLocales = [];
        $baseDir = __DIR__ . '/../../';

        // Scan global translations directory
        $globalTranslationsDir = $baseDir . 'translations';
        if (is_dir($globalTranslationsDir)) {
            $files = scandir($globalTranslationsDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'yaml') {
                    if (strpos($file, 'messages.') === 0) {
                        $foundLocales[] = substr(pathinfo($file, PATHINFO_FILENAME), 9);
                    }
                }
            }
        }

        // Scan module translations directories
        $modulesDir = $baseDir . 'Module';
        if (is_dir($modulesDir)) {
            $modules = scandir($modulesDir);
            foreach ($modules as $module) {
                if ($module === '.' || $module === '..') {
                    continue;
                }
                $moduleTranslationsDir = $modulesDir . '/' . $module . '/translations';
                if (is_dir($moduleTranslationsDir)) {
                    $files = scandir($moduleTranslationsDir);
                    foreach ($files as $file) {
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'yaml') {
                            if (strpos($file, 'messages.') === 0) {
                                $foundLocales[] = substr(pathinfo($file, PATHINFO_FILENAME), 9);
                            }
                        }
                    }
                }
            }
        }
        return array_unique($foundLocales);
    }

    public function getAvailableLocales(): array
    {
        $displayLocales = [];
        foreach ($this->availableLocales as $locale) {
            // Get the name of the locale in the current language
            $displayName = Locales::getName($locale, $this->currentLocale);
            $displayLocales[$locale] = $displayName;
        }
        // Sort by display name for better UX
        asort($displayLocales);
        return $displayLocales;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}