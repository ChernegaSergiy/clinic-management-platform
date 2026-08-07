<?php

namespace App\Bundles\HrmBundle;

use App\Bundles\HrmBundle\DependencyInjection\Compiler\HrmPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HrmBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new HrmPermissionsPass());
    }
}
