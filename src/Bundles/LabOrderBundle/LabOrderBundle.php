<?php

namespace App\Bundles\LabOrderBundle;

use App\Bundles\LabOrderBundle\DependencyInjection\Compiler\LabOrderPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LabOrderBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new LabOrderPermissionsPass());
    }
}
