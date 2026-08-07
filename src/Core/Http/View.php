<?php

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

        $this->addGlobals();
        $this->setupLocale();
    }

    private function addGlobals() : void
    {
        $this->twig->addGlobal('session', $_SESSION);

        $globals = [
            'clinic_name' => 'Міська клінічна лікарня №1',
        ];

        try {
            $conn = $this->registry->getConnection();
            $sql = "SELECT `key`, value FROM settings";
            $result = $conn->executeQuery($sql);
            while ($row = $result->fetchAssociative()) {
                $globals[$row['key']] = $row['value'];
            }
        } catch (\Exception $e) {
            // DB not available
        }

        foreach ($globals as $key => $value) {
            $this->twig->addGlobal($key, $value);
        }
    }

    private function setupLocale() : void
    {
        $globals = $this->twig->getGlobals();
        $preferredLocale = ($globals['system_locale'] ?? null) ?? $this->detectBrowserLanguage() ?? 'uk';
        $rawAvailableLocales = array_keys($this->translationService->getAvailableLocales());
        $finalLocale = 'uk';
        if (in_array($preferredLocale, $rawAvailableLocales)) {
            $finalLocale = $preferredLocale;
        }
        $this->translationService->setLocale($finalLocale);
    }

    private function detectBrowserLanguage() : ?string
    {
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (empty($acceptLang)) {
            return null;
        }

        $supportedLocales = array_keys($this->translationService->getAvailableLocales());

        $languages = explode(',', $acceptLang);
        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = explode('-', $lang)[0];

            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }
        }

        return null;
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
