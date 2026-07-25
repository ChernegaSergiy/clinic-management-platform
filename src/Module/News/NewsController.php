<?php

namespace App\Module\News;


use App\Core\Validation\Validator;
use App\Database\Database;
use App\Module\News\Repository\NewsRepository;
use App\Module\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsController extends \App\Core\Controller\AbstractController
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

    #[Route('/news', name: 'news_index', methods: ['GET'])]
    public function index(): Response
    {
        $newsArticles = $this->newsRepository->findAll();
        return $this->render('news/index.html.twig', [
            'newsArticles' => $newsArticles,
        ]);
    }

    #[Route('/news/{id}', name: 'news_show', methods: ['GET'])]
    public function show(array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $newsArticle = $this->newsRepository->findById($id);

        if (!$newsArticle || !$newsArticle['is_published']) {
            return $this->render('errors/error.html.twig', [
                'message' => 'Новина не знайдена або не опублікована.',
                'detail' => 'Немає статті за вказаним ідентифікатором.'
            ]);
        }

        return $this->render('news/show.html.twig', [
            'newsArticle' => $newsArticle,
        ]);
    }

    #[Route('/admin/news', name: 'admin_news_index', methods: ['GET'])]
    public function adminIndex(): Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $newsArticles = $this->newsRepository->findAll();
        return $this->render('@modules/News/templates/admin/index.html.twig', [
            'newsArticles' => $newsArticles,
        ]);
    }

    #[Route('/admin/news/new', name: 'admin_news_new', methods: ['GET'])]
    public function create(): Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $authorsRaw = $this->userRepository->findAllByRole('admin');
        $authors = array_reduce($authorsRaw, function ($acc, $author) {
            $acc[$author['id']] = $author['full_name'];
            return $acc;
        }, []);

        $response = $this->render('@modules/News/templates/admin/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'authors' => $authors,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/admin/news/new', name: 'admin_news_store', methods: ['POST'])]
    public function store(): Response
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

    #[Route('/admin/news/edit/{id}', name: 'admin_news_edit', methods: ['GET'])]
    public function edit(array $args): Response
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

        $response = $this->render('@modules/News/templates/admin/edit.html.twig', [
            'newsArticle' => $newsArticle,
            'old' => $_SESSION['old'] ?? $newsArticle,
            'errors' => $_SESSION['errors'] ?? [],
            'authors' => $authors,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/admin/news/edit/{id}', name: 'admin_news_update', methods: ['POST'])]
    public function update(array $args): Response
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

    #[Route('/admin/news/delete/{id}', name: 'admin_news_delete', methods: ['POST'])]
    public function delete(array $args): Response
    {
        $this->checkAuth();
        $this->gate->authorize('news.manage');

        $id = (int)($args['id'] ?? 0);
        $this->newsRepository->delete($id);

        return new RedirectResponse('/admin/news');
    }
}
