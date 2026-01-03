<?php

namespace App\Module\Room;

use App\Core\View;
use App\Module\Room\Repository\RoomRepository;
use App\Core\AuthGuard;
use App\Core\Gate;

class RoomController
{
    private RoomRepository $roomRepository;

    public function __construct()
    {
        $this->roomRepository = new RoomRepository();
    }

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

    public function store(): void
    {
        $this->authorizeAdmin();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

        $this->roomRepository->create($_POST);
        $_SESSION['success_message'] = "Кімнату успішно створено.";
        header('Location: /admin/rooms');
        exit();
    }

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

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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