<?php

namespace App\Bundles\KpiBundle;

use App\Bundles\KpiBundle\DependencyInjection\Compiler\KpiPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KpiBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new KpiPermissionsPass());
    }
}
