<?php

namespace App\Module\Appointment;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\Appointment\AppointmentController;

class AppointmentModule extends BaseModule
{
    public function registerRoutes(Router $router): void
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

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('appointments.read', 'Перегляд записів');
        $registry->add('appointments.write', 'Редагування записів');

        $registry->addRoleMapping('admin', ['appointments.read', 'appointments.write']);
        $registry->addRoleMapping('medical_manager', ['appointments.read', 'appointments.write']);
        $registry->addRoleMapping('registrar', ['appointments.read', 'appointments.write']);
        $registry->addRoleMapping('doctor', ['appointments.read', 'appointments.write']);
        $registry->addRoleMapping('nurse', ['appointments.read']);
        $registry->addRoleMapping('billing', ['appointments.read']);
        $registry->addRoleMapping('registrar', ['appointments.read', 'appointments.write']);
        $registry->addRoleMapping('doctor', ['appointments.read', 'appointments.write']);
        $registry->addRoleMapping('nurse', ['appointments.read']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('appointment', AppointmentPolicy::class);
    }
}
