<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Core\Controller;

use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;

abstract class AbstractController
{
    protected AuthGuard $authGuard;
    protected View $view;
    protected Gate $gate;

    /**
     * Set dependencies required by the base controller.
     * This avoids forcing child classes to call parent::__construct with all dependencies.
     */
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setBaseDependencies(AuthGuard $authGuard, View $view, Gate $gate) : void
    {
        $this->authGuard = $authGuard;
        $this->view = $view;
        $this->gate = $gate;
    }

    protected function render(string $template, array $data = []) : \Symfony\Component\HttpFoundation\Response
    {
        $content = $this->view->renderToString($template, $data);
        return new \Symfony\Component\HttpFoundation\Response($content);
    }

    protected function checkAuth() : void
    {
        $this->authGuard->check();
    }
}
