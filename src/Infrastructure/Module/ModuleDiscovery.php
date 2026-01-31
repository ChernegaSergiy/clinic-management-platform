<?php

namespace App\Infrastructure\Module;

use Symfony\Component\Finder\Finder;

final class ModuleDiscovery
{
    /**
     * Discover available modules by scanning the modules directory.
     *
     * @return array List of module names (directory names)
     */
    public function discover(): array
    {
        $modulesPath = __DIR__ . '/../../../Module';
        $finder = new Finder();
        $modules = [];

        if (!is_dir($modulesPath)) {
            return $modules;
        }

        $finder->directories()->in($modulesPath)->depth('== 0');

        foreach ($finder as $dir) {
            $modules[] = $dir->getFilename();
        }

        return $modules;
    }
}
