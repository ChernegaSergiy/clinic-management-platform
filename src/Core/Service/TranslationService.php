<?php

namespace App\Core\Service;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationService
{
    private TranslatorInterface $translator;
    private string $currentLocale = 'uk';

    public function __construct()
    {
        $this->translator = new Translator($this->currentLocale);
        $this->translator->addLoader('yaml', new YamlFileLoader());

        $this->loadTranslations();
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