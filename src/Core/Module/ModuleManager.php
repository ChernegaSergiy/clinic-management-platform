<?php

namespace App\Core\Module;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModuleManager
{
    private array $modules = [];
    private array $loadedModules = [];

    public function register(string $moduleClass): void
    {
        if (isset($this->modules[$moduleClass])) {
            return;
        }

        $this->modules[$moduleClass] = [
            'enabled' => true,
            'class' => $moduleClass,
        ];
    }

    public function unregister(string $moduleClass): void
    {
        unset($this->modules[$moduleClass]);
        if (isset($this->loadedModules[$moduleClass])) {
            unset($this->loadedModules[$moduleClass]);
        }
    }

    public function getModule(string $moduleClass): ?ModuleInterface
    {
        if (!isset($this->loadedModules[$moduleClass])) {
            $this->loadModule($moduleClass);
        }

        return $this->loadedModules[$moduleClass] ?? null;
    }

    public function getLoadedModules(): array
    {
        return array_values($this->loadedModules);
    }

    public function getRegisteredModules(): array
    {
        return array_keys($this->modules);
    }

    public function isLoaded(string $moduleClass): bool
    {
        return isset($this->loadedModules[$moduleClass]);
    }

    public function isEnabled(string $moduleClass): bool
    {
        return $this->modules[$moduleClass]['enabled'] ?? false;
    }

    public function enable(string $moduleClass): void
    {
        $this->modules[$moduleClass]['enabled'] = true;
    }

    public function disable(string $moduleClass): void
    {
        $this->modules[$moduleClass]['enabled'] = false;
    }

    public function bootstrapAll(): void
    {
        foreach ($this->modules as $moduleClass => $config) {
            if (!$config['enabled']) {
                continue;
            }

            $module = $this->getModule($moduleClass);
            if ($module) {
                $module->bootstrap();
            }
        }
    }

    public function registerRoutes(Router $router): void
    {
        foreach ($this->modules as $moduleClass => $config) {
            if (!$config['enabled']) {
                continue;
            }

            $module = $this->getModule($moduleClass);
            if ($module) {
                $module->registerRoutes($router);
            }
        }
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        foreach ($this->modules as $moduleClass => $config) {
            if (!$config['enabled']) {
                continue;
            }

            $module = $this->getModule($moduleClass);
            if ($module) {
                $module->registerPermissions($registry);
            }
        }
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        foreach ($this->modules as $moduleClass => $config) {
            if (!$config['enabled']) {
                continue;
            }

            $module = $this->getModule($moduleClass);
            if ($module) {
                $module->registerPolicies($registry);
            }
        }
    }

    public function registerEventListeners(EventDispatcherInterface $dispatcher): void
    {
        foreach ($this->modules as $moduleClass => $config) {
            if (!$config['enabled']) {
                continue;
            }

            $module = $this->getModule($moduleClass);
            if ($module) {
                $module->registerEventListeners($dispatcher);
            }
        }
    }

    private function loadModule(string $moduleClass): void
    {
        if (!class_exists($moduleClass)) {
            return;
        }

        if (!isset($this->modules[$moduleClass]) || !$this->modules[$moduleClass]['enabled']) {
            return;
        }

        $module = new $moduleClass();
        if (!$module instanceof ModuleInterface) {
            return;
        }

        $this->loadedModules[$moduleClass] = $module;
    }
}
