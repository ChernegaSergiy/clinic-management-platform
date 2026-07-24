<?php

namespace App\Module\Site;

use App\Core\Http\View;
use Symfony\Component\Yaml\Yaml;

class SiteController
{
    public function home(): void
    {
        $content = Yaml::parseFile(__DIR__ . '/../../../content/home.uk.yml');
        View::render('@modules/Site/templates/home/index.html.twig', ['page' => $content]);
    }

    public function about(): void
    {
        View::render('@modules/Site/templates/about/index.html.twig');
    }

    public function contact(): void
    {
        View::render('@modules/Site/templates/contact/index.html.twig');
    }

    public function sitemap(): void
    {
        View::render('@modules/Site/templates/sitemap.html.twig');
    }

    public function privacy(): void
    {
        View::render('@modules/Site/templates/privacy.html.twig');
    }

    public function departments(): void
    {
        View::render('@modules/Site/templates/departments/index.html.twig');
    }

    public function doctors(): void
    {
        View::render('@modules/Site/templates/doctors/index.html.twig');
    }

    public function ourTeam(): void
    {
        View::render('@modules/Site/templates/our-team/index.html.twig');
    }
}
