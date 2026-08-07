<?php

namespace App\Bundles\PatientBundle;

use App\Bundles\PatientBundle\DependencyInjection\Compiler\PatientPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PatientBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new PatientPermissionsPass());
    }
}
