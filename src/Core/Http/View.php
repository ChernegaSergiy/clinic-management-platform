<?php

namespace App\Core\Http;

use App\Core\Auth\MfaGuard;
use App\Core\Service\TranslationService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Doctrine\Persistence\ManagerRegistry;

class View
{
    private ?Environment $twig = null;
    private array $twigGlobals = [];
    private TranslationService $translationService;
    private ManagerRegistry $registry;
    private MfaGuard $mfaGuard;

    public function __construct(TranslationService $translationService, ManagerRegistry $registry, MfaGuard $mfaGuard)
    {
        $this->translationService = $translationService;
        $this->registry = $registry;
        $this->mfaGuard = $mfaGuard;
    }

    private function loadTwigGlobals(): void
    {
        if (!empty($this->twigGlobals)) {
            return;
        }

        $this->twigGlobals = [
            'clinic_name' => 'Міська клінічна лікарня №1',
        ];

        try {
            $conn = $this->registry->getConnection();
            $sql = "SELECT `key`, value FROM settings";
            $result = $conn->executeQuery($sql);
            while ($row = $result->fetchAssociative()) {
                $this->twigGlobals[$row['key']] = $row['value'];
            }
        } catch (\Exception $e) {
            // DB not available
        }
    }

    private function setupLocale(): void
    {
        $this->loadTwigGlobals();

        $preferredLocale = ($this->twigGlobals['system_locale'] ?? null) ?? $this->detectBrowserLanguage() ?? 'uk';
        $rawAvailableLocales = array_keys($this->translationService->getAvailableLocales());
        $finalLocale = 'uk';
        if (in_array($preferredLocale, $rawAvailableLocales)) {
            $finalLocale = $preferredLocale;
        }
        $this->translationService->setLocale($finalLocale);
    }

    private function detectBrowserLanguage(): ?string
    {
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (empty($acceptLang)) {
            return null;
        }

        $supportedLocales = array_keys($this->translationService->getAvailableLocales());

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

    private function getTwig(): Environment
    {
        if ($this->twig === null) {
            $this->setupLocale();

            $loader = new FilesystemLoader(__DIR__ . '/../../../templates');
            $loader->addPath(__DIR__ . '/../../../src/Module', 'modules');
            $this->twig = new Environment($loader, []);
            $this->twig->addGlobal('session', $_SESSION);

            // Add translation extension
            $this->twig->addExtension(new TranslationExtension($this->translationService->getTranslator()));

            foreach ($this->twigGlobals as $key => $value) {
                $this->twig->addGlobal($key, $value);
            }
        }
        return $this->twig;
    }

    public function render(string $template, array $data = []): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        if ($this->mfaGuard->isRequired() && !str_starts_with($requestUri, '/user/mfa/')) {
            header('Location: /user/mfa/required');
            exit();
        }

        echo $this->getTwig()->render($template, $data);
    }

    public function renderToString(string $template, array $data = []): string
    {
        return $this->getTwig()->render($template, $data);
    }

    public function clearCache(): void
    {
        $this->twig = null;
        $this->twigGlobals = [];
    }

    public function getTranslationService(): TranslationService
    {
        return $this->translationService;
    }
}
