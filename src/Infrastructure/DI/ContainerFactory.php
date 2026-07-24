<?php

namespace App\Infrastructure\DI;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContainerFactory
{
    public static function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $projectDir = dirname(__DIR__, 3);
        $container->setParameter('project_dir', $projectDir);

        $servicesFile = $projectDir . '/config/services.php';
        if (file_exists($servicesFile)) {
            $config = require $servicesFile;
            if (is_callable($config)) {
                $config($container);
            }
        }

        $moduleManager = new \App\Core\Module\ModuleManager();
        $loader = new \App\Core\Module\ModuleLoader($moduleManager);
        $loader->loadAll();
        
        foreach ($moduleManager->getRegisteredModules() as $moduleClass) {
            $module = $moduleManager->getModule($moduleClass);
            $module?->registerServices($container);
        }
        
        $container->set(\App\Core\Module\ModuleManager::class, $moduleManager);

        $container->compile();

        return $container;
    }
}
