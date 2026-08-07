<?php

namespace App\Bundles\AppointmentBundle;

use App\Bundles\AppointmentBundle\DependencyInjection\Compiler\AppointmentPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppointmentBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new AppointmentPermissionsPass());
    }
}
