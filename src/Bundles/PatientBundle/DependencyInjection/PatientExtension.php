<?php

namespace App\Bundles\PatientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class PatientExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('patient.features.insurance', $config['features']['insurance']);
        $container->setParameter('patient.features.policies', $config['features']['policies']);
        $container->setParameter('patient.features.export', $config['features']['export']);
    }
}
