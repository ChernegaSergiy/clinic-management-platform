<?php

namespace App\Module\Schedule;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScheduleModule extends BaseModule
{
    public function registerRoutes(Router $router) : void
    {
        $router->add('GET', '/doctor/schedule', [ScheduleController::class, 'index']);
        $router->add('POST', '/doctor/schedule/update', [ScheduleController::class, 'update']);
        $router->add('POST', '/doctor/schedule/exceptions/add', [ScheduleController::class, 'addException']);
        $router->add('POST', '/doctor/schedule/exceptions/delete', [ScheduleController::class, 'deleteException']);

        $router->add('GET', '/admin/schedules', [ScheduleController::class, 'adminIndex']);
        $router->add('GET', '/admin/schedules/show', [ScheduleController::class, 'adminShow']);
        $router->add('GET', '/admin/schedules/edit', [ScheduleController::class, 'adminEdit']);
        $router->add('POST', '/admin/schedules/update', [ScheduleController::class, 'adminUpdate']);
        $router->add('POST', '/admin/schedules/exceptions/add', [ScheduleController::class, 'adminAddException']);
        $router->add('POST', '/admin/schedules/exceptions/delete', [ScheduleController::class, 'adminDeleteException']);
    }

    public function registerServices(ContainerBuilder $container) : void
    {
        $container->register(\App\Module\Schedule\Repository\DoctorScheduleRepository::class)->setPublic(true);
        $container->register(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class)->setPublic(true);
        $container->register(\App\Module\Schedule\Service\SchedulingService::class)
            ->setArguments([
                new Reference(\App\Module\Schedule\Repository\DoctorScheduleRepository::class),
                new Reference(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class),
                new Reference(\App\Bundles\AppointmentBundle\Repository\AppointmentRepository::class),
                new Reference(\App\Module\Billing\Repository\ServiceRepository::class),
                new Reference(\App\Module\Room\Repository\RoomRepository::class),
            ])->setPublic(true);
        $container->register(\App\Module\Schedule\ScheduleController::class)
            ->setArguments([
                new Reference(\App\Module\Schedule\Repository\DoctorScheduleRepository::class),
                new Reference(\App\Module\Schedule\Repository\ScheduleExceptionRepository::class),
                new Reference(\App\Module\User\Repository\UserRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry) : void
    {
        $registry->add('schedules.manage_all', 'Керування всіма розкладами');
        $registry->add('schedules.manage_own', 'Керування власним розкладом');

        $registry->addRoleMapping('admin', ['schedules.manage_all']);
        $registry->addRoleMapping('medical_manager', ['schedules.manage_all']);
        $registry->addRoleMapping('doctor', ['schedules.manage_own']);
    }

    public function registerPolicies(PolicyRegistry $registry) : void
    {
        $registry->register('schedules', SchedulePolicy::class);
    }
}
