<?php

namespace App\Bundles\DashboardBundle;

use App\Bundles\DashboardBundle\DependencyInjection\Compiler\DashboardPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DashboardBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new DashboardPermissionsPass());
    }
}
