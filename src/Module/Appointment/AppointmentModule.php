<?php

namespace App\Module\Appointment;

use App\Core\BaseModule;
use App\Core\Router;
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
}