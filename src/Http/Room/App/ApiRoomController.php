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

namespace App\Http\Room\App;

use App\Domain\Room\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiRoomController extends AbstractController
{
    private RoomRepository $roomRepository;

    public function __construct(RoomRepository $roomRepository)
    {
        $this->roomRepository = $roomRepository;
    }

    #[Route('/api/calendar/rooms', name: 'api_calendar_rooms_get', methods: ['GET'])]
    public function apiRooms() : JsonResponse
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
}
