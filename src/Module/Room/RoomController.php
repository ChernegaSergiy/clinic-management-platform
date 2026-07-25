<?php

namespace App\Module\Room;

use Symfony\Component\Routing\Attribute\Route;
use App\Database\Database;
use App\Core\Auth\Gate;
use App\Core\Validation\Validator;
use App\Module\Room\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class RoomController extends \App\Core\Controller\AbstractController
{
    private RoomRepository $roomRepository;
    private Validator $validator;

    public function __construct(RoomRepository $roomRepository, Validator $validator)
    {
        $this->roomRepository = $roomRepository;
        $this->validator = $validator;
    }

    #[Route('/admin/rooms', name: 'admin_rooms_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->authorizeAdmin();
        $searchTerm = $_GET['search'] ?? '';
        $rooms = $this->roomRepository->findAll();

        return $this->render('@modules/Room/templates/index.html.twig', [
            'rooms' => $rooms,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/admin/rooms/new', name: 'admin_rooms_new_get', methods: ['GET'])]
    public function create(): Response
    {
        $this->authorizeAdmin();

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $this->render('@modules/Room/templates/create.html.twig', [
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/admin/rooms/new', name: 'admin_rooms_new_post', methods: ['POST'])]
    public function store(): Response
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'type' => ['required'],
            'capacity' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/admin/rooms/new');
        }

        $data = $_POST;
        $data['is_available'] = isset($_POST['is_available']) ? 1 : 0;
        $this->roomRepository->create($data);
        $_SESSION['success_message'] = "Кімнату успішно створено.";
        return new RedirectResponse('/admin/rooms');
    }

    #[Route('/admin/rooms/show', name: 'admin_rooms_show_get', methods: ['GET'])]
    public function show(): Response
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            return new Response("Кімнату не знайдено", 404);
        }

        return $this->render('@modules/Room/templates/show.html.twig', ['room' => $room]);
    }

    #[Route('/admin/rooms/edit', name: 'admin_rooms_edit_get', methods: ['GET'])]
    public function edit(): Response
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            return new Response("Кімнату не знайдено", 404);
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $this->render('@modules/Room/templates/edit.html.twig', [
            'room' => $room,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/admin/rooms/edit', name: 'admin_rooms_edit_post', methods: ['POST'])]
    public function update(): Response
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            return new Response("Кімнату не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'type' => ['required'],
            'capacity' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/admin/rooms/edit?id=' . $id);
        }

        $_POST['is_available'] = isset($_POST['is_available']) ? 1 : 0;
        $this->roomRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Дані кімнати успішно оновлено.";
        return new RedirectResponse('/admin/rooms/show?id=' . $id);
    }

    #[Route('/admin/rooms/delete', name: 'admin_rooms_delete_post', methods: ['POST'])]
    public function delete(): Response
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            return new Response("Кімнату не знайдено", 404);
        }

        $this->roomRepository->delete($id);
        $_SESSION['success_message'] = "Кімнату успішно видалено.";
        return new RedirectResponse('/admin/rooms');
    }

    // API endpoints for calendar integration
    #[Route('/api/calendar/rooms', name: 'api_calendar_rooms_get', methods: ['GET'])]
    public function apiRooms(): JsonResponse
    {
        $rooms = $this->roomRepository->findAvailable();
        $resources = [];

        foreach ($rooms as $room) {
            $resources[] = [
                'id' => 'room_' . $room['id'],
                'title' => $room['name'] . ' (' . $room['type'] . ')',
                'type' => 'room',
                'capacity' => $room['capacity'],
                'location' => $room['location'],
                'equipment' => $room['equipment']
            ];
        }

        return new JsonResponse($resources);
    }

    private function authorizeAdmin(): void
    {
        $this->checkAuth();
        Gate::authorize('rooms.manage');
    }
}
