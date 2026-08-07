<?php

namespace App\Bundles\AppointmentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AppointmentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('appointment.features.waitlist', $config['features']['waitlist']);
        $container->setParameter('appointment.features.api', $config['features']['api']);
    }
}
