<?php

namespace App\Core\Module;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseModule implements ModuleInterface
{
    protected string $manifestPath;
    protected ?array $manifest = null;
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = $this->getBasePath();
        $this->manifestPath = $this->basePath . '/module.yaml';
    }

    public function getName(): string
    {
        return $this->getManifest()['name'] ?? $this->getDefaultName();
    }

    public function getVersion(): string
    {
        return $this->getManifest()['version'] ?? '1.0.0';
    }

    public function registerServices(ContainerBuilder $container): void
    {
    }

    public function bootstrap(): void
    {
    }

    public function registerRoutes(Router $router): void
    {
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }

    public function registerEventListeners(EventDispatcherInterface $dispatcher): void
    {
    }

    protected function getManifest(): array
    {
        if ($this->manifest === null) {
            if (file_exists($this->manifestPath)) {
                $yaml = file_get_contents($this->manifestPath);
                $this->manifest = \Symfony\Component\Yaml\Yaml::parse($yaml);
            } else {
                $this->manifest = [];
            }
        }

        return $this->manifest;
    }

    protected function getDefaultName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        return str_replace('Module', '', $className);
    }

    protected function getBasePath(): string
    {
        $reflector = new \ReflectionClass($this);
        return dirname($reflector->getFileName());
    }

    protected function getConfig(string $key, $default = null)
    {
        return $this->getManifest()['config'][$key] ?? $default;
    }
}
