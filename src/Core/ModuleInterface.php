<?php

namespace App\Core;

interface ModuleInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function bootstrap(): void;

    public function registerRoutes(Router $router): void;

    public function registerPermissions(PermissionRegistry $registry): void;

    public function registerPolicies(PolicyRegistry $registry): void;
}