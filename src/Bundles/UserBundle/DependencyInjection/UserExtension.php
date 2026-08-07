<?php

namespace App\Bundles\UserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class UserExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('user.features.oauth', $config['features']['oauth']);
        $container->setParameter('user.features.profile_photo', $config['features']['profile_photo']);
    }
}
