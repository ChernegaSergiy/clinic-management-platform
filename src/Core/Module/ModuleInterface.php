<?php

namespace App\Core\Module;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;

interface ModuleInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function bootstrap(): void;

    public function registerRoutes(Router $router): void;

    public function registerPermissions(PermissionRegistry $registry): void;

    public function registerPolicies(PolicyRegistry $registry): void;
}
