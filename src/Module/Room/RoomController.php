<?php

namespace App\Module\Room;

use Symfony\Component\Routing\Attribute\Route;
use App\Database\Database;
use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Core\Validation\Validator;
use App\Module\Room\Repository\RoomRepository;

class RoomController
{
    private RoomRepository $roomRepository;
    private Validator $validator;

    public function __construct(RoomRepository $roomRepository, Validator $validator)
    {
        $this->roomRepository = $roomRepository;
        $this->validator = $validator;
    }

    #[Route('/admin/rooms', name: 'admin_rooms_index', methods: ['GET'])]
    public function index(): void
    {
        $this->authorizeAdmin();
        $searchTerm = $_GET['search'] ?? '';
        $rooms = $this->roomRepository->findAll();

        View::render('@modules/Room/templates/index.html.twig', [
            'rooms' => $rooms,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/admin/rooms/new', name: 'admin_rooms_new_get', methods: ['GET'])]
    public function create(): void
    {
        $this->authorizeAdmin();

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/Room/templates/create.html.twig', [
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/admin/rooms/new', name: 'admin_rooms_new_post', methods: ['POST'])]
    public function store(): void
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
            header('Location: /admin/rooms/new');
            exit();
        }

        $data = $_POST;
        $data['is_available'] = isset($_POST['is_available']) ? 1 : 0;
        $this->roomRepository->create($data);
        $_SESSION['success_message'] = "Кімнату успішно створено.";
        header('Location: /admin/rooms');
        exit();
    }

    #[Route('/admin/rooms/show', name: 'admin_rooms_show_get', methods: ['GET'])]
    public function show(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            http_response_code(404);
            echo "Кімнату не знайдено";
            return;
        }

        View::render('@modules/Room/templates/show.html.twig', ['room' => $room]);
    }

    #[Route('/admin/rooms/edit', name: 'admin_rooms_edit_get', methods: ['GET'])]
    public function edit(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            http_response_code(404);
            echo "Кімнату не знайдено";
            return;
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/Room/templates/edit.html.twig', [
            'room' => $room,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/admin/rooms/edit', name: 'admin_rooms_edit_post', methods: ['POST'])]
    public function update(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            http_response_code(404);
            echo "Кімнату не знайдено";
            return;
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
            header('Location: /admin/rooms/edit?id=' . $id);
            exit();
        }

        $_POST['is_available'] = isset($_POST['is_available']) ? 1 : 0;
        $this->roomRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Дані кімнати успішно оновлено.";
        header('Location: /admin/rooms/show?id=' . $id);
        exit();
    }

    #[Route('/admin/rooms/delete', name: 'admin_rooms_delete_post', methods: ['POST'])]
    public function delete(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $room = $this->roomRepository->findById($id);

        if (!$room) {
            http_response_code(404);
            echo "Кімнату не знайдено";
            return;
        }

        $this->roomRepository->delete($id);
        $_SESSION['success_message'] = "Кімнату успішно видалено.";
        header('Location: /admin/rooms');
        exit();
    }

    // API endpoints for calendar integration
    #[Route('/api/calendar/rooms', name: 'api_calendar_rooms_get', methods: ['GET'])]
    public function apiRooms(): void
    {
        header('Content-Type: application/json');

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

        echo json_encode($resources);
    }

    private function authorizeAdmin(): void
    {
        AuthGuard::check();
        Gate::authorize('rooms.manage');
    }
}
