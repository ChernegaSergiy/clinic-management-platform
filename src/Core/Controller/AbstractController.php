<?php

namespace App\Core\Controller;

use App\Core\Auth\AuthGuard;
use App\Core\Http\View;

abstract class AbstractController
{
    protected AuthGuard $authGuard;
    protected View $view;

    /**
     * Set dependencies required by the base controller.
     * This avoids forcing child classes to call parent::__construct with all dependencies.
     */
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setBaseDependencies(AuthGuard $authGuard, View $view): void
    {
        $this->authGuard = $authGuard;
        $this->view = $view;
    }

    protected function render(string $template, array $data = []): \Symfony\Component\HttpFoundation\Response
    {
        $content = $this->view->renderToString($template, $data);
        return new \Symfony\Component\HttpFoundation\Response($content);
    }

    protected function checkAuth(): void
    {
        $this->authGuard->check();
    }
}
