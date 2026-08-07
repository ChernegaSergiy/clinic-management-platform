<?php

namespace App\Bundles\AdminBundle;

use App\Bundles\AdminBundle\DependencyInjection\Compiler\AdminPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AdminBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new AdminPermissionsPass());
    }
}
