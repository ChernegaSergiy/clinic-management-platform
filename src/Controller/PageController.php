<?php

namespace App\Controller;

use App\Core\View;
use Symfony\Component\Yaml\Yaml;

class PageController
{
    public function home(): void
    {
        $content = Yaml::parseFile(__DIR__ . '/../../content/home.uk.yml');
        View::render('home/index.html.twig', ['page' => $content]);
    }

    public function about(): void
    {
        View::render('about/index.html.twig');
    }

    public function contact(): void
    {
        View::render('contact/index.html.twig');
    }

    public function sitemap(): void
    {
        View::render('sitemap.html.twig');
    }

    public function privacy(): void
    {
        View::render('privacy.html.twig');
    }
}
