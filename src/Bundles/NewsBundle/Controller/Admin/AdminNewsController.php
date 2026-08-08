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

namespace App\Bundles\NewsBundle\Controller\Admin;

use App\Bundles\NewsBundle\Repository\NewsRepository;
use App\Bundles\UserBundle\Repository\UserRepositoryInterface;
use App\Core\Validation\Validator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminNewsController extends \App\Core\Controller\AbstractController
{
    private NewsRepository $newsRepository;
    private UserRepositoryInterface $userRepository;
    private Validator $validator;

    public function __construct(NewsRepository $newsRepository, UserRepositoryInterface $userRepository, Validator $validator)
    {
        $this->newsRepository = $newsRepository;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    #[Route('/news', name: 'admin_news_index', methods: ['GET'])]
    public function adminIndex() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $newsArticles = $this->newsRepository->findAll();
        return $this->render('@News/index.html.twig', [
            'newsArticles' => $newsArticles,
        ]);
    }

    #[Route('/news/new', name: 'admin_news_new', methods: ['GET'])]
    public function create() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $authorsRaw = $this->userRepository->findAllByRole('admin');
        $authors = array_reduce($authorsRaw, function ($acc, $author) {
            $acc[$author['id']] = $author['full_name'];
            return $acc;
        }, []);

        $response = $this->render('@News/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'authors' => $authors,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/news/new', name: 'admin_news_store', methods: ['POST'])]
    public function store() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'title' => ['required', 'string', 'max:255'],
            'meta' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'published_at' => ['required', 'datetime'],
            'author_id' => ['required', 'numeric'],
            'is_published' => ['boolean'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/admin/news/new');
        }

        $this->newsRepository->create($_POST);
        return new RedirectResponse('/admin/news');
    }

    #[Route('/news/edit/{id}', name: 'admin_news_edit', methods: ['GET'])]
    public function edit(array $args) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $id = (int)($args['id'] ?? 0);
        $newsArticle = $this->newsRepository->findById($id);

        if (!$newsArticle) {
            return $this->render('errors/error.html.twig', [
                'message' => 'Новина не знайдена.',
                'detail' => 'Немає статті за вказаним ідентифікатором.'
            ]);
        }

        $authorsRaw = $this->userRepository->findAllByRole('admin');
        $authors = array_reduce($authorsRaw, function ($acc, $author) {
            $acc[$author['id']] = $author['full_name'];
            return $acc;
        }, []);

        $response = $this->render('@News/edit.html.twig', [
            'newsArticle' => $newsArticle,
            'old' => $_SESSION['old'] ?? $newsArticle,
            'errors' => $_SESSION['errors'] ?? [],
            'authors' => $authors,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/news/edit/{id}', name: 'admin_news_update', methods: ['POST'])]
    public function update(array $args) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $id = (int)($args['id'] ?? 0);
        $newsArticle = $this->newsRepository->findById($id);

        if (!$newsArticle) {
            return $this->render('errors/error.html.twig', [
                'message' => 'Новина не знайдена.',
                'detail' => 'Немає статті за вказаним ідентифікатором.'
            ]);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'title' => ['required', 'string', 'max:255'],
            'meta' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'published_at' => ['required', 'datetime'],
            'author_id' => ['required', 'numeric'],
            'is_published' => ['boolean'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse("/admin/news/edit/{$id}");
        }

        $this->newsRepository->update($id, $_POST);
        return new RedirectResponse('/admin/news');
    }

    #[Route('/news/delete/{id}', name: 'admin_news_delete', methods: ['POST'])]
    public function delete(array $args) : Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $id = (int)($args['id'] ?? 0);
        $this->newsRepository->delete($id);

        return new RedirectResponse('/admin/news');
    }
}
