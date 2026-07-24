<?php

namespace App\Core\Module;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface ModuleInterface
{
    public function getName(): string;

    public function getVersion(): string;

    /**
     * Єдине місце, де бандл реєструє власні сервіси в контейнер.
     * Викликається ДО compile(), на етапі складання контейнера.
     */
    public function registerServices(ContainerBuilder $container): void;

    public function bootstrap(): void;

    public function registerRoutes(Router $router): void;

    public function registerPermissions(PermissionRegistry $registry): void;

    public function registerPolicies(PolicyRegistry $registry): void;

    public function registerEventListeners(EventDispatcherInterface $dispatcher): void;
}
