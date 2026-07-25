<?php

namespace App\Module\News;

use App\Core\Auth\Gate;
use App\Core\Validation\Validator;
use App\Database\Database;
use App\Module\News\Repository\NewsRepository;
use App\Module\User\Repository\UserRepositoryInterface;

// To get author info

class NewsController extends \App\Core\Controller\AbstractController
{
    private NewsRepository $newsRepository;
    private UserRepositoryInterface $userRepository; // For author selection in admin forms
    private Validator $validator;

    public function __construct(NewsRepository $newsRepository, UserRepositoryInterface $userRepository, Validator $validator)
    {
        $this->newsRepository = $newsRepository;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    // Public listing of news articles
    public function index(): void
    {
        $newsArticles = $this->newsRepository->findAll();
        $this->render('news/index.html.twig', [
            'newsArticles' => $newsArticles,
        ]);
    }

    // Public view of a single news article
    public function show(array $args): void
    {
        $id = (int)($args['id'] ?? 0);
        $newsArticle = $this->newsRepository->findById($id);

        if (!$newsArticle || !$newsArticle['is_published']) {
            $this->render('errors/error.html.twig', [
                'message' => 'Новина не знайдена або не опублікована.',
                'detail' => 'Немає статті за вказаним ідентифікатором.'
            ]);
            return;
        }

        $this->render('news/show.html.twig', [
            'newsArticle' => $newsArticle,
        ]);
    }

    // Admin: List all news articles
    public function adminIndex(): void
    {
        $this->checkAuth();
        Gate::authorize('news.manage');

        $newsArticles = $this->newsRepository->findAll();
        $this->render('@modules/News/templates/admin/index.html.twig', [
            'newsArticles' => $newsArticles,
        ]);
    }

    // Admin: Show form to create a new news article
    public function create(): void
    {
        $this->checkAuth();
        Gate::authorize('news.manage');

        $authorsRaw = $this->userRepository->findAllByRole('admin');
        $authors = array_reduce($authorsRaw, function ($acc, $author) {
            $acc[$author['id']] = $author['full_name'];
            return $acc;
        }, []);

        $this->render('@modules/News/templates/admin/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'authors' => $authors,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    // Admin: Store a new news article
    public function store(): void
    {
        $this->checkAuth();
        Gate::authorize('news.manage');

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
            header('Location: /admin/news/new');
            exit();
        }

        $this->newsRepository->create($_POST);
        header('Location: /admin/news');
        exit();
    }

    // Admin: Show form to edit a news article
    public function edit(array $args): void
    {
        $this->checkAuth();
        Gate::authorize('news.manage');

        $id = (int)($args['id'] ?? 0);
        $newsArticle = $this->newsRepository->findById($id);

        if (!$newsArticle) {
            $this->render('errors/error.html.twig', [
                'message' => 'Новина не знайдена.',
                'detail' => 'Немає статті за вказаним ідентифікатором.'
            ]);
            return;
        }

        $authorsRaw = $this->userRepository->findAllByRole('admin');
        $authors = array_reduce($authorsRaw, function ($acc, $author) {
            $acc[$author['id']] = $author['full_name'];
            return $acc;
        }, []);

        $this->render('@modules/News/templates/admin/edit.html.twig', [
            'newsArticle' => $newsArticle,
            'old' => $_SESSION['old'] ?? $newsArticle,
            'errors' => $_SESSION['errors'] ?? [],
            'authors' => $authors,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    // Admin: Update a news article
    public function update(array $args): void
    {
        $this->checkAuth();
        Gate::authorize('news.manage');

        $id = (int)($args['id'] ?? 0);
        $newsArticle = $this->newsRepository->findById($id);

        if (!$newsArticle) {
            $this->render('errors/error.html.twig', [
                'message' => 'Новина не знайдена.',
                'detail' => 'Немає статті за вказаним ідентифікатором.'
            ]);
            return;
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
            header("Location: /admin/news/edit/{$id}");
            exit();
        }

        $this->newsRepository->update($id, $_POST);
        header('Location: /admin/news');
        exit();
    }

    // Admin: Delete a news article
    public function delete(array $args): void
    {
        $this->checkAuth();
        Gate::authorize('news.manage');

        $id = (int)($args['id'] ?? 0);
        $this->newsRepository->delete($id);

        header('Location: /admin/news');
        exit();
    }
}
