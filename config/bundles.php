<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    App\Bundles\DashboardBundle\DashboardBundle::class => ['all' => true],
    App\Bundles\PatientBundle\PatientBundle::class => ['all' => true],
    App\Bundles\AppointmentBundle\AppointmentBundle::class => ['all' => true],
    App\Bundles\UserBundle\UserBundle::class => ['all' => true],
    App\Bundles\ScheduleBundle\ScheduleBundle::class => ['all' => true],
    App\Bundles\MedicalRecordBundle\MedicalRecordBundle::class => ['all' => true],
    App\Bundles\RoomBundle\RoomBundle::class => ['all' => true],
];
