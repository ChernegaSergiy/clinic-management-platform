<?php

namespace App\Core\Module;

class ModuleLoader
{
    private ModuleManager $moduleManager;
    private string $modulesPath;
    private string $namespacePrefix;

    public function __construct(
        ModuleManager $moduleManager,
        string $modulesPath = __DIR__ . '/../../Module',
        string $namespacePrefix = 'App\\Module\\'
    ) {
        $this->moduleManager = $moduleManager;
        $this->modulesPath = $modulesPath;
        $this->namespacePrefix = $namespacePrefix;
    }

    public function loadAll(): void
    {
        $modules = $this->discoverModules();

        foreach ($modules as $moduleName => $moduleClass) {
            $this->moduleManager->register($moduleClass);
        }
    }

    public function loadFromConfig(array $config): void
    {
        foreach ($config as $moduleName => $moduleConfig) {
            if (!($moduleConfig['enabled'] ?? true)) {
                continue;
            }

            $moduleClass = $moduleConfig['class'] ?? $this->namespacePrefix . $moduleName . 'Module';

            if (class_exists($moduleClass)) {
                $this->moduleManager->register($moduleClass);
            }
        }
    }

    public function discoverModules(): array
    {
        $modules = [];

        if (!is_dir($this->modulesPath)) {
            return $modules;
        }

        $dirs = scandir($this->modulesPath);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || !is_dir($this->modulesPath . '/' . $dir)) {
                continue;
            }

            $moduleName = $dir;
            $moduleClass = $this->namespacePrefix . $moduleName . '\\' . $moduleName . 'Module';

            if (class_exists($moduleClass)) {
                $reflection = new \ReflectionClass($moduleClass);

                if ($reflection->implementsInterface(ModuleInterface::class)) {
                    $modules[$moduleName] = $moduleClass;
                }
            }
        }

        return $modules;
    }

    public function getModuleManager(): ModuleManager
    {
        return $this->moduleManager;
    }
}
