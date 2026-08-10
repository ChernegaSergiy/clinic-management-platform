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

namespace App\Bundles\ScheduleBundle\Controller\App;

use App\Bundles\ScheduleBundle\Repository\DoctorScheduleRepository;
use App\Bundles\ScheduleBundle\Repository\ScheduleExceptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppScheduleController extends AbstractController
{
    private DoctorScheduleRepository $doctorScheduleRepository;
    private ScheduleExceptionRepository $scheduleExceptionRepository;

    public function __construct(DoctorScheduleRepository $doctorScheduleRepository, ScheduleExceptionRepository $scheduleExceptionRepository)
    {
        $this->doctorScheduleRepository = $doctorScheduleRepository;
        $this->scheduleExceptionRepository = $scheduleExceptionRepository;
    }

    #[Route('/doctor/schedule', name: 'doctor_schedule_index', methods: ['GET'])]
    public function index() : Response
    {
        $this->denyAccessUnlessGranted('SCHEDULE_MANAGE_OWN');

        $userId = (int)$_SESSION['user']['id'];
        $schedule = $this->doctorScheduleRepository->findByDoctor($userId);
        $exceptions = $this->scheduleExceptionRepository->findByDoctorAndDateRange(
            $userId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 year'))
        );

        $scheduleByDay = [];
        foreach ($schedule as $entry) {
            $scheduleByDay[$entry['day_of_week']] = $entry;
        }

        return $this->render('schedule/personal.html.twig', [
            'scheduleByDay' => $scheduleByDay,
            'exceptions' => $exceptions,
        ]);
    }

    #[Route('/doctor/schedule/update', name: 'doctor_schedule_update', methods: ['POST'])]
    public function update() : Response
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $targetDoctorId = $sessionUserId; // Default

        if ($this->isGranted('SCHEDULE_MANAGE_ALL') && isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] > 0) {
            $targetDoctorId = (int)$_POST['doctor_id'];
        }

        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== $sessionUserId) {
            $this->denyAccessUnlessGranted('SCHEDULE_MANAGE_ALL');
        }

        $scheduleData = $_POST['schedule'] ?? [];

        foreach ($scheduleData as $dayOfWeek => $data) {
            $existingSchedule = $this->doctorScheduleRepository->findByDoctorAndDay($targetDoctorId, (int)$dayOfWeek);

            $scheduleEntry = [
                'doctor_id' => $targetDoctorId,
                'day_of_week' => (int)$dayOfWeek,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'is_available' => isset($data['is_available']) ? 1 : 0,
            ];

            if ($existingSchedule) {
                $this->doctorScheduleRepository->update($existingSchedule['id'], $scheduleEntry);
            } else {
                $this->doctorScheduleRepository->create($scheduleEntry);
            }
        }

        $redirectUrl = ($targetDoctorId !== $sessionUserId) ? 'admin_schedules_index' : 'doctor_schedule_index';
        return $this->redirectToRoute($redirectUrl);
    }

    #[Route('/doctor/schedule/exceptions/add', name: 'doctor_schedule_exceptions_add', methods: ['POST'])]
    public function addException() : Response
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $targetDoctorId = $sessionUserId; // Default

        if ($this->isGranted('SCHEDULE_MANAGE_ALL') && isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] > 0) {
            $targetDoctorId = (int)$_POST['doctor_id'];
        }

        // Authorize if attempting to modify another user's schedule
        if ($targetDoctorId !== $sessionUserId) {
            $this->denyAccessUnlessGranted('SCHEDULE_MANAGE_ALL');
        }

        $exceptionData = [
            'doctor_id' => $targetDoctorId,
            'exception_date' => $_POST['exception_date'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'is_available' => (int)$_POST['is_available'],
            'notes' => $_POST['notes'] ?? null,
        ];

        $this->scheduleExceptionRepository->create($exceptionData);

        $redirectUrl = ($targetDoctorId !== $sessionUserId) ? 'admin_schedules_index' : 'doctor_schedule_index';
        return $this->redirectToRoute($redirectUrl);
    }

    #[Route('/doctor/schedule/exceptions/delete', name: 'doctor_schedule_exceptions_delete', methods: ['POST'])]
    public function deleteException() : Response
    {
        $sessionUserId = (int)$_SESSION['user']['id'];
        $exceptionId = (int)$_POST['exception_id'];

        $exception = $this->scheduleExceptionRepository->findById($exceptionId);

        if (!$exception) {
            return $this->redirectToRoute('doctor_schedule_index');
        }

        $targetDoctorId = (int)$exception['doctor_id'];

        // Authorize if attempting to delete another user's exception
        if ($targetDoctorId !== $sessionUserId) {
            $this->denyAccessUnlessGranted('SCHEDULE_MANAGE_ALL');
        }

        // Final check that exception belongs to the intended targetDoctorId
        if ($targetDoctorId === $sessionUserId || $this->isGranted('SCHEDULE_MANAGE_ALL')) {
            $this->scheduleExceptionRepository->delete($exceptionId);
        }

        $redirectUrl = ($targetDoctorId !== $sessionUserId) ? 'admin_schedules_index' : 'doctor_schedule_index';
        return $this->redirectToRoute($redirectUrl);
    }
}
