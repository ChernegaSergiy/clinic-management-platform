<?php

namespace App\Infrastructure\Module;

use Symfony\Component\Finder\Finder;

final class ModuleDiscovery
{
    private string $modulesPath;

    public function __construct(string $modulesPath)
    {
        $this->modulesPath = $modulesPath;
    }

    /**
     * Discover available modules by scanning the modules directory.
     *
     * @return array List of module names (directory names)
     */
    public function discover() : array
    {
        $finder = new Finder();
        $modules = [];

        if (!is_dir($this->modulesPath)) {
            return $modules;
        }

        $finder->directories()->in($this->modulesPath)->depth('== 0');

        foreach ($finder as $dir) {
            $modules[] = $dir->getFilename();
        }

        return $modules;
    }
}
