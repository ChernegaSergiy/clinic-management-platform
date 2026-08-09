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

namespace App\Bundles\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SiteController extends AbstractController
{
    #[Route('/', name: 'site_home')]
    public function home() : Response
    {
        return $this->render('@Site/home.html.twig');
    }

    #[Route('/about', name: 'site_about')]
    public function about() : Response
    {
        return $this->render('@Site/about.html.twig');
    }

    #[Route('/contact', name: 'site_contact')]
    public function contact() : Response
    {
        return $this->render('@Site/contact.html.twig');
    }

    #[Route('/sitemap', name: 'site_sitemap')]
    public function sitemap() : Response
    {
        return $this->render('@Site/sitemap.html.twig');
    }

    #[Route('/privacy', name: 'site_privacy')]
    public function privacy() : Response
    {
        return $this->render('@Site/privacy.html.twig');
    }

    #[Route('/departments', name: 'site_departments')]
    public function departments() : Response
    {
        return $this->render('@Site/departments.html.twig');
    }

    #[Route('/doctors', name: 'site_doctors')]
    public function doctors() : Response
    {
        return $this->render('@Site/doctors.html.twig');
    }

    #[Route('/our-team', name: 'site_our_team')]
    public function ourTeam() : Response
    {
        return $this->render('@Site/our-team.html.twig');
    }
}
