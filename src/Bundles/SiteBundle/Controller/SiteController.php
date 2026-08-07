<?php

namespace App\Bundles\SiteBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SiteController extends \App\Core\Controller\AbstractController
{
    #[Route('/', name: 'site_home')]
    public function home() : Response
    {
        return $this->render('@Site/home/index.html.twig');
    }

    #[Route('/about', name: 'site_about')]
    public function about() : Response
    {
        return $this->render('@Site/about/index.html.twig');
    }

    #[Route('/contact', name: 'site_contact')]
    public function contact() : Response
    {
        return $this->render('@Site/contact/index.html.twig');
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
        return $this->render('@Site/departments/index.html.twig');
    }

    #[Route('/doctors', name: 'site_doctors')]
    public function doctors() : Response
    {
        return $this->render('@Site/doctors/index.html.twig');
    }

    #[Route('/our-team', name: 'site_our_team')]
    public function ourTeam() : Response
    {
        return $this->render('@Site/our-team/index.html.twig');
    }
}
