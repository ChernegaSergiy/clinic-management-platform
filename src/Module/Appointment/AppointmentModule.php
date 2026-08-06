<?php

namespace App\Module\Appointment;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AppointmentModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/appointments', [AppointmentController::class, 'index']);
        $router->add('GET', '/appointments/new', [AppointmentController::class, 'create']);
        $router->add('POST', '/appointments/new', [AppointmentController::class, 'store']);
        $router->add('GET', '/appointments/show', [AppointmentController::class, 'show']);
        $router->add('GET', '/appointments/edit', [AppointmentController::class, 'edit']);
        $router->add('POST', '/appointments/edit', [AppointmentController::class, 'update']);
        $router->add('POST', '/appointments/cancel', [AppointmentController::class, 'cancel']);
        $router->add('GET', '/book-appointment', [AppointmentController::class, 'publicForm']);
        $router->add('POST', '/book-appointment', [AppointmentController::class, 'submitPublicForm']);

        if ($this->getConfig('features.waitlist', true)) {
            $router->add('GET', '/appointments/waitlist', [AppointmentController::class, 'showWaitlist']);
            $router->add('POST', '/appointments/waitlist/reject', [AppointmentController::class, 'rejectWaitlist']);
            $router->add('GET', '/appointments/waitlist/fulfill', [AppointmentController::class, 'fulfillWaitlist']);
            $router->add('POST', '/appointments/waitlist/cancel', [AppointmentController::class, 'cancelWaitlist']);
        }

        if ($this->getConfig('features.api', true)) {
            $router->add('GET', '/api/appointments', [AppointmentController::class, 'json']);
            $router->add('GET', '/api/appointments/available-slots', [AppointmentController::class, 'getAvailableSlotsApi']);
        }
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Appointment\Repository\AppointmentRepository::class)->setPublic(true);
        $container->register(\App\Module\Appointment\AppointmentController::class)
            ->setArguments([
                new Reference(\App\Module\Appointment\Repository\AppointmentRepository::class),
                new Reference(\App\Module\Patient\Repository\PatientRepository::class),
                new Reference(\App\Module\User\Repository\UserRepository::class),
                new Reference(\App\Core\Service\NotificationService::class),
                new Reference(\App\Module\Schedule\Service\SchedulingService::class),
                new Reference(\App\Module\Schedule\Repository\DoctorScheduleRepository::class),
                new Reference(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class),
                new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
                new Reference(\App\Module\Room\Repository\RoomRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('appointment.view.any', 'Перегляд будь-якого запису');
        $registry->add('appointment.view.own', 'Перегляд власних записів');
        $registry->add('appointment.edit.any', 'Редагування будь-якого запису');
        $registry->add('appointment.edit.own', 'Редагування власних записів');
        $registry->add('appointment.create', 'Створення записів');

        $registry->addRoleMapping('admin', ['appointment.view.any', 'appointment.edit.any', 'appointment.create']);
        $registry->addRoleMapping('medical_manager', ['appointment.view.any', 'appointment.edit.any', 'appointment.create']);
        $registry->addRoleMapping('registrar', ['appointment.view.any', 'appointment.edit.any', 'appointment.create']);
        $registry->addRoleMapping('doctor', ['appointment.view.own', 'appointment.edit.own', 'appointment.create']);
        $registry->addRoleMapping('nurse', ['appointment.view.own']);
        $registry->addRoleMapping('billing', ['appointment.view.any']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('appointment', AppointmentPolicy::class);
    }
}
