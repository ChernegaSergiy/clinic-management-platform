<?php

namespace App\Module\Site;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

class SiteController extends \App\Core\Controller\AbstractController
{
    #[Route('/', name: 'site_home')]
    public function home(): Response
    {
        $content = Yaml::parseFile(__DIR__ . '/../../../content/home.uk.yml');
        return new Response($this->view->renderToString('@modules/Site/templates/home/index.html.twig', ['page' => $content]));
    }

    #[Route('/about', name: 'site_about')]
    public function about(): Response
    {
        return new Response($this->view->renderToString('@modules/Site/templates/about/index.html.twig'));
    }

    #[Route('/contact', name: 'site_contact')]
    public function contact(): Response
    {
        return new Response($this->view->renderToString('@modules/Site/templates/contact/index.html.twig'));
    }

    #[Route('/sitemap', name: 'site_sitemap')]
    public function sitemap(): Response
    {
        return new Response($this->view->renderToString('@modules/Site/templates/sitemap.html.twig'));
    }

    #[Route('/privacy', name: 'site_privacy')]
    public function privacy(): Response
    {
        return new Response($this->view->renderToString('@modules/Site/templates/privacy.html.twig'));
    }

    #[Route('/departments', name: 'site_departments')]
    public function departments(): Response
    {
        return new Response($this->view->renderToString('@modules/Site/templates/departments/index.html.twig'));
    }

    #[Route('/doctors', name: 'site_doctors')]
    public function doctors(): Response
    {
        return new Response($this->view->renderToString('@modules/Site/templates/doctors/index.html.twig'));
    }

    #[Route('/our-team', name: 'site_our_team')]
    public function ourTeam(): Response
    {
        return new Response($this->view->renderToString('@modules/Site/templates/our-team/index.html.twig'));
    }
}
