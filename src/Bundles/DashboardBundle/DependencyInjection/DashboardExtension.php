<?php

namespace App\Bundles\DashboardBundle\DependencyInjection;

use App\Bundles\DashboardBundle\DashboardPolicy;
use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class DashboardExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('dashboard.features.export', $config['features']['export']);
    }
}
